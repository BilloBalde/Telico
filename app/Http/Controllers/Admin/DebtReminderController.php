<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DebtReminder;
use App\Services\SmsService;

class DebtReminderController extends Controller
{
    public function __construct(private SmsService $sms)
    {
    }

    /**
     * Liste des rappels de dettes groupés par client, avec liens WhatsApp.
     */
    public function index()
    {
        // Client archivé : chargé quand même, sinon la ligne n'a plus de client
        // et la page entière tombait en erreur (bouton « Résolu » sans identifiant).
        $reminders = DebtReminder::with(['customer' => fn ($q) => $q->withTrashed()])
            ->visibleTo(auth()->user())
            ->where('status', '!=', 'resolved')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($group) {
                $customer = $group->first()->customer;

                return (object) [
                    'customer'      => $customer,
                    'customer_id'   => $group->first()->customer_id,
                    'total'         => $group->sum('amount'),
                    'count'         => $group->count(),
                    'max_reminders' => $group->max('reminder_count'),
                    'last_sent_at'  => $group->max('last_sent_at'),
                    'reminder_ids'  => $group->pluck('id'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('admin.debt-reminders.index', compact('reminders'));
    }

    /**
     * Marquer tous les rappels d'un client comme résolus.
     */
    public function resolve(int $customerId)
    {
        // Seulement les rappels du périmètre de l'utilisateur.
        DebtReminder::where('customer_id', $customerId)
            ->visibleTo(auth()->user())
            ->where('status', '!=', 'resolved')
            ->update(['status' => 'resolved']);

        return back()->with('success', 'Rappels marqués comme résolus.');
    }

    /**
     * Envoie manuellement un rappel par SMS à un client — même
     * principe que le bouton WhatsApp existant, mais déclenché explicitement
     * par le staff (pas d'envoi automatique).
     *
     * Ce projet n'a aucun fournisseur SMS configuré : l'envoi échoue et le message
     * est journalisé. Renseigner SMS_DRIVER pour le rendre opérationnel.
     */
    public function sendSms(int $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $tel = preg_replace('/\D/', '', $customer->tel ?? '');
        if (strlen($tel) < 8) {
            return back()->with('error', 'Numéro de téléphone invalide pour ce client.');
        }

        $reminders = DebtReminder::where('customer_id', $customerId)
            ->visibleTo(auth()->user())
            ->where('status', '!=', 'resolved')
            ->get();
        $total = $reminders->sum('amount');

        if ($total <= 0) {
            return back()->with('error', 'Aucune dette active pour ce client.');
        }

        $message = 'Bonjour ' . ($customer->customerName ?? 'cher client')
            . ', vous avez une facture impayée de '
            . number_format($total, 0, ',', ' ')
            . ' GNF chez ' . (\App\Models\Company::current()?->name ?? config('app.name'))
            . '. Veuillez régler votre dette avant lundi. Merci de votre confiance.';

        $sent = $this->sms->send($tel, $message);

        if ($sent) {
            foreach ($reminders as $reminder) {
                $reminder->update([
                    'reminder_count' => $reminder->reminder_count + 1,
                    'last_sent_at'   => now(),
                    'status'         => 'sent',
                ]);
            }

            return back()->with('success', "SMS envoyé à {$customer->customerName}.");
        }

        return back()->with('error', "Échec de l'envoi du SMS à {$customer->customerName} — vérifiez les logs.");
    }
}
