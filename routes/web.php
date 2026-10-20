<?php

use App\Exports\ProductsExport;
use App\Exports\PurchasesExport;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Authentification;
use App\Http\Controllers\BaseDonneeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CurrencySettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Ecommerce\AddressController;
use App\Http\Controllers\Ecommerce\AuthOtpController;
use App\Http\Controllers\Ecommerce\CartController;
use App\Http\Controllers\Ecommerce\OrderController;
use App\Http\Controllers\Ecommerce\OrangeMoneyController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\JournalierController;
use App\Http\Controllers\LogisticController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentSettingController;
use App\Http\Controllers\PhoneAuthController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\DeviController;
use App\Http\Controllers\NewExportController;
use App\Http\Controllers\PurchaseOrderController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| WEB ROUTES - SMH
|--------------------------------------------------------------------------
*/

// =============================================
// 1. ROUTES PUBLIQUES - PAGE D'ACCUEIL
// =============================================

// Page d'accueil
Route::get('/', [VisitController::class, 'index'])->name('accueil');

// Redirection vers login
Route::get('/login-redirect', function () {
    return redirect()->route('login');
})->name('login.redirect');

// =============================================
// 2. ROUTES D'AUTHENTIFICATION (COMBINÉES)
// =============================================

// Authentification standard
Route::get('/login', [Authentification::class, 'login'])->name('login');
Route::get('/register', [Authentification::class, 'register'])->middleware(['auth', 'permission:users.create'])->name('addUser');
Route::put('/updateUser/{id}', [Authentification::class, 'update'])->middleware(['auth', 'permission:users.edit'])->name('updateUser');
Route::get('/edit/{user}', [Authentification::class, 'edit'])->middleware(['auth', 'permission:users.edit'])->name('editUser');
// Pointé vers UserController@destroy : Authentification n'a jamais eu de méthode destroy (500 garanti avant)
Route::delete('/deleteUser/{id}', [UserController::class, 'destroy'])->middleware(['auth', 'permission:users.delete'])->name('deleteUser');
Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware(['auth', 'permission:users.edit'])->name('users.toggleStatus');
Route::get('/forgotPass', [Authentification::class, 'forgotPass'])->name('forgotPass');
Route::get('/registration/verification/{token}/{email}', [Authentification::class, 'registration_verify'])->name('verification');
Route::post('/register', [Authentification::class, 'create'])->middleware(['auth', 'permission:users.create'])->name('enregistrer');
Route::post('/login_submit', [Authentification::class, 'login_submit'])->middleware('throttle:10,1')->name('login_submit');
Route::post('/passwordRecovery', [Authentification::class, 'passwordRecovery'])->middleware('throttle:5,1')->name('passwordRecovery');
Route::post('/sendEmail', [Authentification::class, 'sendEmail'])->name('sendEmail');

// Authentification par téléphone
Route::post('/send-verification-code', [PhoneAuthController::class, 'sendCode'])->middleware('throttle:5,1')->name('send.code');
Route::post('/verify-and-login', [PhoneAuthController::class, 'verifyAndLogin'])->middleware('throttle:10,1')->name('verify.login');
Route::post('/logout', [PhoneAuthController::class, 'logout'])->name('shop.logout.post');

// Mot de passe oublié
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::get('password/reset/{token}/{email}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/misajour', [ForgotPasswordController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');

// =============================================
// 3. ROUTES DE DÉCONNEXION
// =============================================

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('accueil')->with('success', 'Déconnecté avec succès');
})->middleware('auth.check')->name('logout');

Route::get('/logout', function () {
    return redirect()->route('login');
});

// =============================================
// 4. ROUTES FRONTEND - E-COMMERCE
// =============================================

// Pages statiques
Route::get('/about', function () {
    return view('visitor.about', ['pageName' => 'about-page']);
})->name('about');

Route::get('/contact', function () {
    return view('visitor.contact');
})->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

// Messages de contact (admin)
Route::get('/admin/messages', [ContactController::class, 'index'])->name('contact.messages')->middleware('auth.check');
Route::post('/admin/messages/{id}/read', [ContactController::class, 'markRead'])->name('contact.read')->middleware('auth.check');
Route::delete('/admin/messages/{id}', [ContactController::class, 'destroy'])->name('contact.destroy')->middleware('auth.check');

// Newsletter
Route::post('/newsletter', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    return redirect()->route('accueil')->with('newsletter_success', 'Merci ! Vous êtes bien inscrit à notre newsletter.');
})->name('newsletter.subscribe');

// Catalogue et produits publics
Route::get('/catalogue', [VisitController::class, 'indexProducts'])->name('products.index');
Route::get('/nos-categories', [VisitController::class, 'publicCategories'])->name('public.categories');
Route::post('/produits/load-more', [VisitController::class, 'loadMoreProducts'])->name('products.loadMore');
Route::get('/product/{id}', [VisitController::class, 'showProduct'])->name('productDetail');

// Route beauté
Route::get('/beauty', [VisitController::class, 'index']);

// =============================================
// 5. ROUTES SHOP - PANIER & COMMANDES
// =============================================

Route::get('/panier', [CartController::class, 'show'])->name('panier');
Route::post('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

Route::prefix('shop')->group(function () {
    // Authentification OTP
    Route::get('/register', [AuthOtpController::class, 'showRegister'])->name('otp.register');
    Route::post('/register', [AuthOtpController::class, 'register'])->middleware('throttle:5,1')->name('otp.register_submit');
    Route::get('/login', [AuthOtpController::class, 'showLogin'])->name('otp.login');
    Route::post('/login', [AuthOtpController::class, 'login'])->middleware('throttle:5,1')->name('otp.login_submit');
    // Étape de vérification OTP retirée du parcours : le client se connecte
    // directement avec son numéro. Les méthodes showVerify()/verify() et tout
    // le mécanisme (OtpCode, AuthService, SmsService) restent en place ; il
    // suffit de rétablir ces deux routes pour les remettre en service.
    Route::post('/logout', [AuthOtpController::class, 'logout'])->name('shop.logout');

    // Routes client connecté
    Route::middleware(['auth', 'customer'])->group(function () {
        Route::get('/', [VisitController::class, 'shop'])->name('shop.home');
        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create', [AddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        
        // Checkout et commandes
        Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
        Route::get('/buy-now/{id}', [OrderController::class, 'buyNow'])->name('buy.now');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

        // Paiement Orange Money
        Route::get('/payment/pay/{order}', [OrangeMoneyController::class, 'pay'])->name('payment.pay');
        Route::get('/payment/success', [OrangeMoneyController::class, 'success'])->name('payment.success');
        Route::get('/payment/cancel', [OrangeMoneyController::class, 'cancel'])->name('payment.cancel');
    });
});

// Webhook Orange Money (sans CSRF)
Route::post('/payment/webhook', [OrangeMoneyController::class, 'webhook'])
    ->name('payment.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// =============================================
// NOTIFICATIONS IN-APP (staff + clients)
// =============================================

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [\App\Http\Controllers\NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

// =============================================
// 6. ROUTES ADMIN - GESTION E-COMMERCE
// =============================================

Route::middleware(['auth', 'superuser'])->prefix('admin')->group(function () {
    Route::get('/orders', [OrderManagementController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/confirmed', [OrderManagementController::class, 'confirmed'])->name('admin.orders.confirmed');
    Route::get('/orders/factures', [OrderManagementController::class, 'factures'])->name('admin.orders.factures');
    Route::get('/orders/{order}', [OrderManagementController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{order}/approve', [OrderManagementController::class, 'approve'])->middleware('permission:orders.validate')->name('admin.orders.approve');
    Route::post('/orders/{order}/reject', [OrderManagementController::class, 'reject'])->middleware('permission:orders.validate')->name('admin.orders.reject');
    // Traitement au siège par défaut ; attribution possible à une boutique.
    Route::post('/orders/{order}/assign', [OrderManagementController::class, 'assign'])->name('admin.orders.assign');
    Route::post('/orders/{order}/release', [OrderManagementController::class, 'release'])->middleware('permission:orders.view')->name('admin.orders.release');
    Route::get('/orders/{order}/stock-check', [OrderManagementController::class, 'stockCheck'])->name('admin.orders.stock.check');
    Route::get('/orders/facture/{facture}/items', [OrderManagementController::class, 'getFactureItems'])->name('admin.orders.facture.items');
    Route::post('/orders/facture/{facture}/deliver', [OrderManagementController::class, 'partialDeliver'])->middleware('permission:orders.deliver')->name('admin.orders.facture.deliver');

    // Rappels de dettes (digest hebdomadaire + envoi WhatsApp/SMS manuel)
    Route::get('/debt-reminders', [\App\Http\Controllers\Admin\DebtReminderController::class, 'index'])->name('admin.debt-reminders.index');
    Route::post('/debt-reminders/{customerId}/resolve', [\App\Http\Controllers\Admin\DebtReminderController::class, 'resolve'])->name('admin.debt-reminders.resolve');
    Route::post('/debt-reminders/{customerId}/send-sms', [\App\Http\Controllers\Admin\DebtReminderController::class, 'sendSms'])->name('admin.debt-reminders.send-sms');
});

// =============================================
// 7. ROUTES BACKEND - DASHBOARD
// =============================================

Route::get('/home', [IndexController::class, 'index'])->middleware('auth.check')->name('home');

// =============================================
// 8. ROUTES PROFIL & UTILISATEURS
// =============================================

Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/emailSetting', [EmailController::class, 'index'])->name('emailSetting');
Route::post('/profileImage', [ProfileController::class, 'profileImage'])->name('profileImage');
Route::post('/profileInfo', [ProfileController::class, 'profileInfo'])->name('profileInfo');
Route::post('/password', [ProfileController::class, 'passwordupdate'])->name('passwordupdate');
Route::post('/compagnie', [ProfileController::class, 'companyCreate'])->name('companyCreate');

// Gestion des utilisateurs
// destroy est exclu : la suppression passe uniquement par /deleteUser/{id}, qui exige
// permission:users.delete (cette route resource ne vérifiait qu'auth.check, donc
// n'importe quel employé connecté pouvait supprimer n'importe quel compte).
Route::resource('users', UserController::class)->except(['destroy']);
// Ecran d'affectation a une boutique retire : la vue users.assign_store n'a
// jamais existe, et le champ « boutique » est deja present dans les
// formulaires de creation et de modification d'un utilisateur.

// =============================================
// 9. ROUTES PARAMÈTRES
// =============================================

// Ces routes n'avaient aucun middleware (ni auth, ni permission) : accessibles
// même sans être connecté. Découvert en testant le rôle Manager, qui doit
// justement ne PAS avoir accès aux paramètres.
Route::middleware(['auth', 'permission:settings.view'])->group(function () {
    Route::resource('paymentSetting', PaymentSettingController::class);
    Route::resource('currencySetting', CurrencySettingController::class);
    Route::resource('roles', RoleController::class)->except(['show']);
});

// =============================================
// 10. ROUTES BOUTIQUES & LIEUX
// =============================================

Route::resource('boutiques', StoreController::class);
Route::post('/boutiques/quick-create', [StoreController::class, 'quickStore'])->middleware('auth.check')->name('boutiques.quickCreate');

Route::get('/places', [PlaceController::class, 'index'])->name('places.index');
Route::get('/places/{place}', [PlaceController::class, 'edit'])->name('places.edit');
Route::get('/createplace', [PlaceController::class, 'create'])->name('places.create');
Route::post('/createplace', [PlaceController::class, 'store'])->name('places.store');
Route::delete('/places/{id}', [PlaceController::class, 'destroy'])->name('places.destroy');
Route::put('/updateplace/{place}', [PlaceController::class, 'update'])->name('places.update');

// =============================================
// 11. ROUTES CLIENTS
// =============================================

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/creditors', [CustomerController::class, 'creditors'])->name('customers.creditors');
Route::get('/createcustomer', [CustomerController::class, 'create'])->name('customers.create');
Route::post('/createcustomer', [CustomerController::class, 'store'])->name('customers.store');
Route::get('/customers/{customer}', [CustomerController::class, 'edit'])->name('customers.edit');
Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
Route::delete('/customers/{id}/force', [CustomerController::class, 'forceDelete'])->name('customers.forceDelete');
// Pas de route « corbeille » : CustomerController n'a pas de méthode trashed() et
// aucune vue ne l'accompagne. restore() et forceDelete() ci-dessus restent en place,
// ils fonctionnent et constituent le seul moyen de récupérer un client supprimé.
Route::post('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
Route::post('/customers/quick-add', [CustomerController::class, 'quickAdd'])->name('customers.quick-add');

// =============================================
// 12. ROUTES CATÉGORIES & PRODUITS
// =============================================

// show() exclue : la methode n'a jamais ete ecrite (corps vide) et attendait
// une classe CategoryEmballage qui n'existe pas dans ce projet. Les categories
// se consultent depuis la liste et le formulaire de modification.
Route::resource('categories', CategoryController::class)->except(['show']);
// Le paramètre est contraint aux identifiants numériques : sans cela,
// « produits/export-pdf » est lu comme l'élément dont l'identifiant vaut
// « export-pdf », et les routes d'export déclarées plus bas ne sont
// jamais atteintes — elles renvoyaient un 404.
Route::resource('produits', ProductController::class)->where(['produit' => '[0-9]+']);
Route::post('/produits/quick-create', [ProductController::class, 'quickStore'])->middleware('auth.check')->name('produits.quickCreate');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
Route::get('/fetch-product-details', [ProductController::class, 'fetchProductDetails'])->middleware('auth.check');
Route::get('/fetch-product-details-suggestion', [ProductController::class, 'fetchProductDetailsSuggestion'])->middleware('auth.check');

// =============================================
// 13. ROUTES DÉPENSES
// =============================================

Route::resource('expensesCategory', ExpenseCategoryController::class);
Route::resource('expenses', ExpenseController::class)->except(['show']);

// =============================================
// 14. ROUTES ACHATS
// =============================================

// Le paramètre est contraint aux identifiants numériques : sans cela,
// « purchases/export-pdf » est lu comme l'élément dont l'identifiant vaut
// « export-pdf », et les routes d'export déclarées plus bas ne sont
// jamais atteintes — elles renvoyaient un 404.
// create() exclue : cet ecran de saisie a ete remplace par le module Achats
// (purchase-orders). Le controleur ne transmet plus $numeroPurchase, $store_id
// ni $products, la vue tombait donc en erreur.
Route::resource('purchases', PurchaseController::class)
    ->except(['create'])
    ->where(['purchase' => '[0-9]+']);
Route::resource('purchase-orders', PurchaseOrderController::class);
Route::get('/purchases/ajout/{numeroPurchase}/{quantity}/{store}', [PurchaseController::class, 'ajout'])->name('purchases.ajout');
Route::post('/purchases/exitAchat/{numeroPurchase}', [PurchaseController::class, 'exitAchat'])->name('exitPurchase');

// =============================================
// 15. ROUTES FACTURES
// =============================================

Route::resource('factures', FactureController::class)->except(['create']);
Route::get('/factures/bon-de-commande/{facture}', [FactureController::class, 'bonDeCommande'])->name('factures.bon-de-commande');
Route::get('/factures/bon-de-sortie/{facture}', [FactureController::class, 'bonDeSortie'])->name('factures.bon-de-sortie');

// =============================================
// 16. ROUTES VENTES
// =============================================

// create() exclue en plus : cet ecran a ete remplace par le point de vente
// (/pos). Le controleur ne transmet plus $numero_facture a la vue.
Route::resource('sales', SaleController::class)->except(['show', 'destroy', 'create']);
Route::get('/ventes/export-excel', [SaleController::class, 'exportExcel'])->name('sales.export-excel');
Route::get('/ventes/export-pdf', [SaleController::class, 'exportPDF'])->name('sales.export-pdf');
Route::get('/ventes/export-current-excel', [SaleController::class, 'exportCurrentExcel'])->name('sales.export-current-excel');
Route::get('/ventes/export-current-pdf', [SaleController::class, 'exportCurrentPDF'])->name('sales.export-current-pdf');
Route::get('/sales/ajout/{numero_facture}/{avance}/{store_id}', [SaleController::class, 'ajout'])->name('sales.ajout');
Route::post('/sales/exitSale/{numero_facture}', [SaleController::class, 'exitSale'])->name('exitSale');
Route::get('/sales/voir/{numero_facture}', [SaleController::class, 'voirSales'])->middleware('auth.check')->name('voirSales');

// =============================================
// 17. ROUTES PAIEMENTS
// =============================================

// Les routes spécifiques doivent être déclarées AVANT Route::resource : sinon
// "/payments/export-pdf" est capturé par "/payments/{payment}" (show()) et plante.
Route::get('/payments/export-pdf', [PaymentController::class, 'exportPdf'])->name('payments.export');
Route::get('/payments/export-excel', [PaymentController::class, 'exportExcel'])->name('payments.exportExcel');
Route::resource('payments', PaymentController::class)->only(['index', 'store']);
Route::get('/facture/payment/{id}', [PaymentController::class, 'creation'])->name('payments.creation');
Route::get('/facture/voirPayment/{id}', [PaymentController::class, 'voir'])->name('payments.voir');
Route::get('/paiementsClient/add/{mark}', [PaymentController::class, 'add'])->name('paiementsClient.add');
Route::post('/paiementsClient/store', [PaymentController::class, 'storePayment'])->name('paiementsClient.store');
Route::get('/updateCustomerTotals', [PaymentController::class, 'updateCustomerTotals'])->middleware('auth.check');

// =============================================
// 18. ROUTES REÇUS
// =============================================

Route::get('/receipts/payments/{payment}', [ReceiptController::class, 'payment'])->name('receipts.payments.show');
Route::get('/receipts/transactions/{transaction}', [ReceiptController::class, 'transaction'])->name('receipts.transactions.show');
Route::get('/receipts/transfers/{transfer}', [ReceiptController::class, 'transfer'])->name('receipts.transfers.show');

// =============================================
// 19. ROUTES RAPPORTS
// =============================================

Route::get('/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales');
Route::get('/reports/ventes-flash', [SalesReportController::class, 'flash'])->middleware(['auth.check', 'permission:reports.view'])->name('reports.flash');
// Ventes tierces : produits pris chez un partenaire, dû et règlements aux partenaires.
Route::get('/reports/ventes-tierces', [SalesReportController::class, 'tiers'])->middleware(['auth.check', 'permission:reports.view'])->name('reports.tiers');
Route::get('/reports/ventes-tierces/export-pdf', [SalesReportController::class, 'tiersPdf'])->middleware(['auth.check', 'permission:reports.view'])->name('reports.tiers.pdf');
Route::get('/reports/ventes-tierces/export-excel', [SalesReportController::class, 'tiersExcel'])->middleware(['auth.check', 'permission:reports.view'])->name('reports.tiers.excel');
Route::post('/reports/ventes-tierces/reglements', [SalesReportController::class, 'reglerPartenaire'])->middleware(['auth.check', 'permission:partners.settle'])->name('reports.tiers.settle');
Route::get('/reports/daily', [DailyReportController::class, 'index'])->name('reports.daily');
Route::get('/reports/daily/export/pdf', [DailyReportController::class, 'exportPDF'])->name('reports.daily.export.pdf');

// =============================================
// 20. ROUTES MOUVEMENTS
// =============================================

Route::get('/mouvements', [MovementController::class, 'index'])->name('movements.index');
Route::get('/mouvements/pdf', [MovementController::class, 'exportPdf'])->name('movements.pdf');

// =============================================
// 21. ROUTES LOGISTIQUE
// =============================================

// Le paramètre est contraint aux identifiants numériques : sans cela,
// « logistics/export-pdf » est lu comme l'élément dont l'identifiant vaut
// « export-pdf », et les routes d'export déclarées plus bas ne sont
// jamais atteintes — elles renvoyaient un 404.
Route::resource('logistics', LogisticController::class)->where(['logistic' => '[0-9]+']);
Route::get('/logistics/export-excel', [LogisticController::class, 'exportExcel'])->middleware('auth.check')->name('logistics.export-excel');
Route::get('/logistics/export-pdf', [LogisticController::class, 'exportPDF'])->middleware('auth.check')->name('logistics.export-pdf');

// =============================================
// 22. ROUTES TRANSFERTS
// =============================================

Route::resource('transfers', StoreProductController::class);
// Associés : fonds, compte à part, relevé (lecture seule pour l'associé lui-même).
Route::get('/associes/mon-releve', [\App\Http\Controllers\FundController::class, 'me'])->name('associates.me');
Route::get('/associes', [\App\Http\Controllers\FundController::class, 'index'])->name('associates.index');
Route::get('/associes/nouveau', [\App\Http\Controllers\FundController::class, 'create'])->name('associates.create');
Route::post('/associes', [\App\Http\Controllers\FundController::class, 'store'])->name('associates.store');
Route::get('/associes/{fund}', [\App\Http\Controllers\FundController::class, 'show'])->whereNumber('fund')->name('associates.show');
Route::get('/associes/{fund}/modifier', [\App\Http\Controllers\FundController::class, 'edit'])->whereNumber('fund')->name('associates.edit');
Route::put('/associes/{fund}', [\App\Http\Controllers\FundController::class, 'update'])->whereNumber('fund')->name('associates.update');
Route::post('/associes/{fund}/mouvements', [\App\Http\Controllers\FundController::class, 'storeTransaction'])->whereNumber('fund')->name('associates.transactions.store');
// Sections : entrepôt / magasin principal et chaque boutique. Le propriétaire
// entre dans une section ou revient à la vue globale ; un employé reste dans la sienne.
Route::get('/sections', [\App\Http\Controllers\SectionController::class, 'index'])->name('sections.index');
Route::post('/sections/switch', [\App\Http\Controllers\SectionController::class, 'switch'])->name('sections.switch');
// Stock par emplacement : vue globale, fiche emplacement, journal, traçabilité, inventaire.
Route::get('/stock', [\App\Http\Controllers\StockController::class, 'index'])->name('stock.index');
Route::get('/stock/mouvements', [\App\Http\Controllers\StockController::class, 'movements'])->name('stock.movements');
Route::get('/stock/tracabilite', [\App\Http\Controllers\StockController::class, 'trace'])->name('stock.trace');
Route::get('/stock/inventaire', [\App\Http\Controllers\StockController::class, 'adjustForm'])->name('stock.adjust.form');
Route::post('/stock/inventaire', [\App\Http\Controllers\StockController::class, 'adjust'])->name('stock.adjust');
Route::get('/stock/{store}', [\App\Http\Controllers\StockController::class, 'show'])->whereNumber('store')->name('stock.show');
// Circuit : demandé → autorisé → expédié (sortie source) → réceptionné (entrée destination).
Route::post('/transfers/{transfer}/approve', [StoreProductController::class, 'approve'])->name('transfers.approve');
Route::post('/transfers/{transfer}/reject', [StoreProductController::class, 'reject'])->name('transfers.reject');
Route::post('/transfers/{transfer}/ship', [StoreProductController::class, 'ship'])->name('transfers.ship');
Route::post('/transfers/{transfer}/receive', [StoreProductController::class, 'receive'])->name('transfers.receive');
Route::post('/transfers/{transfer}/cancel', [StoreProductController::class, 'cancel'])->middleware('auth.check')->name('transfers.cancel');

// =============================================
// 23. (ancien module Proformas/LigneCommande — retiré, remplacé par le module
//     Achat / PurchaseOrder ci-dessus, section "ROUTES BOUTIQUES & LIEUX" /
//     purchase-orders.*)
// =============================================

// =============================================
// 24. ROUTES DEVIS
// =============================================

Route::resource('devis', DeviController::class);
Route::post('/devis/{id}/status', [DeviController::class, 'changeStatus'])->name('devis.status');
Route::post('/devis/{id}/validate', [DeviController::class, 'validateDevis'])->name('devis.validate');
Route::get('/devis/{id}/pdf', [DeviController::class, 'showPdf'])->name('devis.pdf');
Route::get('/devis/{id}/pdf-download', [DeviController::class, 'downloadPdf'])->name('devis.pdf-download');

// =============================================
// 27. ROUTES FACTURATIONS
// =============================================

Route::get('/facturations/add', [FactureController::class, 'facturationForm'])->name('facturations.add');
Route::post('/facturations/store', [FactureController::class, 'facturationStore'])->name('facturations.store');
Route::post('/facturations/storeCustomer', [FactureController::class, 'storeCustomer'])->name('facturations.storeCustomer');
Route::delete('/facturations/{facture}', [FactureController::class, 'destroy'])->name('facturations.destroy');

// =============================================
// 28. ROUTES JOURNALIER
// =============================================

Route::get('/journalier', [JournalierController::class, 'index'])->name('journaliers.index');
Route::get('/journalier/create', [JournalierController::class, 'create'])->name('journaliers.create');
Route::post('/journalier', [JournalierController::class, 'store'])->name('journaliers.store');
Route::get('/journalier/{id}', [JournalierController::class, 'edit'])->name('journaliers.edit');
Route::put('/journalier/{id}', [JournalierController::class, 'update'])->name('journaliers.update');
Route::delete('/journalier/{id}', [JournalierController::class, 'destroy'])->name('journaliers.delete');
Route::get('/journaliers/paiements', [JournalierController::class, 'paymentsIndex'])->name('journaliers.payments.index');
Route::get('/journalier/paiementList/{id}', [JournalierController::class, 'list'])->name('journaliers.paymentVoir');
Route::get('/journalier/paiementForm/{id}', [JournalierController::class, 'payForm'])->name('journaliers.paymentForm');
Route::post('/journalier/paiement', [JournalierController::class, 'paySubmit'])->name('journalier.pay');

// =============================================
// 29. ROUTES POS (Point de Vente)
// =============================================

Route::get('pos', [SaleController::class, 'pos'])->middleware('auth.check')->name('pos');
Route::post('pos/storeCustomer', [SaleController::class, 'storeCustomer'])->middleware('auth.check')->name('pos.storeCustomer');
// Routes pour les partenaires et ventes tierces
Route::post('/pos/store-partner', [SaleController::class, 'storePartner'])->name('pos.storePartner');
Route::post('/pos/quick-third-party', [SaleController::class, 'storeQuickThirdParty'])->name('pos.storeQuickThirdParty');
// =============================================
// 30. ROUTES EXPORTS (Combinées)
// =============================================

// Export Produits
Route::get('/produits/export-excel', function () {
    try {
        return Excel::download(new ProductsExport, 'products.xlsx');
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware('auth.check')->name('produits.export-excel');

Route::get('/produits/export-pdf', function () {
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);
    
    try {
        $dataTable = Product::with('categories', 'stores')->get();
        $userStoreId = auth()->user()->role_id == 3
                ? Store::where('user_id', auth()->user()->id)->value('id')
                : null;
        
        $pdf = Pdf::loadView('products.export', compact('dataTable', 'userStoreId'));
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => public_path(),
        ]);
        
        return $pdf->download('products_' . date('Y-m-d') . '.pdf');
    } catch (\Exception $e) {
        return back()->with('error', 'Erreur: ' . $e->getMessage());
    }
})->middleware('auth.check')->name('produits.export-pdf');

// Export « en file d'attente » retire, avec le travail ExportProductsJob :
// aucun bouton n'y menait, le travail appelait auth()->loginUsingId() qui
// n'existe pas hors session, et QUEUE_CONNECTION=sync l'executait de toute
// facon en ligne. L'export direct /produits/export-excel fait le travail.

Route::get('/produits/export-csv', function () {
    $filename = 'products_' . date('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['Nom', 'SKU', 'Catégories', 'Stock', 'Prix']);
        
        Product::with('categories', 'stores')->chunk(100, function($products) use ($handle) {
            foreach ($products as $product) {
                if (auth()->user()->role_id == 3) {
                    $storeId = Store::where('user_id', auth()->id())->value('id');
                    $store = $product->stores->firstWhere('id', $storeId);
                    $stock = $store ? $store->pivot->quantity : 0;
                } else {
                    $stock = $product->stores->sum('pivot.quantity');
                }
                
                $categories = $product->categories->pluck('slug')->implode('|');
                
                fputcsv($handle, [
                    $product->libelle,
                    $product->sku,
                    $categories,
                    $stock,
                    $product->price_sale_ctn ?? 0
                ]);
            }
        });
        
        fclose($handle);
    };
    
    return response()->stream($callback, 200, $headers);
})->middleware('auth.check')->name('produits.export-csv');

Route::get('/produits/download-export', function() {
    $file = session('export_file');
    if ($file && Storage::exists(str_replace('/storage', 'public', $file))) {
        return response()->download(storage_path('app/public/' . str_replace('/storage/', '', $file)));
    }
    return back()->with('error', 'Fichier non trouvé');
})->middleware('auth.check')->name('produits.download-export');

// Export Achats
Route::get('/purchases/export-excel', function () {
    return Excel::download(new PurchasesExport, 'purchases.xlsx');
})->middleware(['auth.check', 'permission:purchases.view'])->name('purchases.export-excel');

Route::get('/purchases/export-pdf', function () {
    $dataTable = \App\Models\Purchase::all();
    $pdf = Pdf::loadView('purchases.export', compact('dataTable'));
    return $pdf->download('purchases.pdf');
})->middleware(['auth.check', 'permission:purchases.view'])->name('purchases.export-pdf');

// =============================================
// 31. NOUVELLES ROUTES D'EXPORT
// =============================================

Route::prefix('new-exports')->middleware('auth.check')->name('new.')->group(function () {
    Route::get('/products/excel', [NewExportController::class, 'exportExcel'])->name('products.excel');
    Route::get('/products/pdf', [NewExportController::class, 'exportPdf'])->name('products.pdf');
    Route::get('/products/pdf-simple', [NewExportController::class, 'exportPdfSimple'])->name('products.pdf-simple');
    Route::get('/products/csv', [NewExportController::class, 'exportCsv'])->name('products.csv');
});

Route::get('/exports/products/html', [NewExportController::class, 'exportHtmlReport'])->middleware('auth.check')->name('exports.products.html');

// =============================================
// 32. ROUTES BASE DE DONNÉES
// =============================================

Route::get('/baseDonnee', [BaseDonneeController::class, 'index'])->name('baseDonnee.index');
Route::post('/baseDonnee/{id}/delete', [BaseDonneeController::class, 'delete'])->name('deleteLines');

// =============================================
// 33. ROUTES CLEAR CACHE
// =============================================

Route::get('/clear-cache', function() {
    if (!auth()->check() || auth()->user()->role_id != 1) {
        abort(403);
    }
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return view('refreshed');
})->middleware('auth.check')->name('clear-cache');

// =============================================
// 34. ROUTES DE REDIRECTION
// =============================================

Route::get('/home-redirect', function () {
    return redirect()->route('accueil');
});

// =============================================
// 36. PAGE 404
// =============================================

// Aucune route fallback : Laravel rend `resources/views/errors/404.blade.php`
// de lui-même pour toute URL inconnue, avec le statut 404. L'ancien fallback
// renvoyait cette page avec un statut 200, ce qui la faisait passer pour une
// page valide auprès des navigateurs et des moteurs de recherche.