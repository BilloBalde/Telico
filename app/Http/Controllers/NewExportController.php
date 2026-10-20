<?php
// app/Http/Controllers/NewExportController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class NewExportController extends Controller
{
    /**
     * Export Excel optimisé
     */
    public function exportExcel()
    {
        // Augmenter les ressources
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
        
        try {
            // Charger les produits par lots
            $products = Product::with(['categories', 'stores'])
                        ->select('id', 'libelle', 'sku', 'price', 'image', 'updated_at')
                        ->get();
            
            // Préparer les données pour l'export
            $exportData = [];
            
            foreach ($products as $product) {
                // Calculer la quantité selon le rôle
                $quantity = $this->calculateQuantity($product);
                
                // Récupérer les catégories
                $categories = $product->categories->pluck('slug')->implode(', ');
                
                $exportData[] = [
                    'Nom' => $product->libelle,
                    'SKU' => $product->sku,
                    'Catégories' => $categories,
                    'Quantité' => $quantity,
                    // Unité unique du produit : stock et prix sont dans cette unité.
                    'Unité' => $product->unite_libelle,
                    'Prix' => $product->price ?? 0,
                    'Date' => $product->updated_at ? $product->updated_at->format('d/m/Y') : '',
                ];
            }
            
            // Créer le fichier Excel
            return Excel::download(
                new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray, 
                                                        \Maatwebsite\Excel\Concerns\WithHeadings,
                                                        \Maatwebsite\Excel\Concerns\WithTitle {
                    protected $data;
                    
                    public function __construct($data)
                    {
                        $this->data = $data;
                    }
                    
                    public function array(): array
                    {
                        return $this->data;
                    }
                    
                    public function headings(): array
                    {
                        return ['Nom', 'SKU', 'Catégories', 'Quantité', 'Unité', 'Prix par unité (F)', 'Date'];
                    }
                    
                    public function title(): string
                    {
                        return 'Produits';
                    }
                },
                'produits_' . date('Y-m-d') . '.xlsx'
            );
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    public function exportHtmlReport()
    {
        $products = Product::with(['categories', 'stores'])->get();
        
        // Optionnel: Filtrer les produits par boutique si nécessaire
        if (auth()->check() && auth()->user()->isStoreScoped()) {
            $storeId = auth()->user()->assignedStoreId();
            // Vous pouvez ajouter un filtre ici si besoin
        }
        
        return view('products.products-html', compact('products'));
    }
    
    /**
     * Export PDF complet (avec images)
     */
    public function exportPdf()
    {
        // Augmenter les ressources
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);
        
        try {
            $products = Product::with(['categories', 'stores'])->get();
            
            $html = $this->generatePdfHtml($products, true);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]);
            
            return $pdf->download('produits_complets_' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Export PDF simplifié (SANS images) - RECOMMANDÉ
     */
    public function exportPdfSimple()
    {
        // Ressources normales car pas d'images
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 120);
        
        try {
            // Charger sans les relations lourdes
            $products = Product::with('categories')
                        ->select('id', 'libelle', 'sku', 'price', 'updated_at')
                        ->get();
            
            $html = $this->generatePdfHtml($products, false);
            
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            
            return $pdf->download('produits_simples_' . date('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Export CSV (ultra léger)
     */
    public function exportCsv()
    {
        try {
            $filename = 'produits_' . date('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            
            $callback = function() {
                $handle = fopen('php://output', 'w');
                
                // En-têtes UTF-8 avec BOM pour Excel
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // En-têtes CSV
                fputcsv($handle, ['Nom', 'SKU', 'Catégories', 'Stock', 'Unité', 'Prix par unité (F)', 'Date']);
                
                // Traiter par lots
                Product::with('categories', 'stores')
                    ->chunk(100, function($products) use ($handle) {
                        foreach ($products as $product) {
                            $quantity = $this->calculateQuantity($product);
                            $categories = $product->categories->pluck('slug')->implode('|');
                            
                            fputcsv($handle, [
                                $product->libelle,
                                $product->sku,
                                $categories,
                                $quantity,
                                $product->unite_libelle,
                                $product->price ?? 0,
                                $product->updated_at ? $product->updated_at->format('d/m/Y') : '',
                            ]);
                        }
                    });
                
                fclose($handle);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculer la quantité selon le rôle
     */
    private function calculateQuantity($product)
    {
        if (auth()->check() && auth()->user()->isStoreScoped()) {
            $storeId = auth()->user()->assignedStoreId();
            $store = $product->stores->firstWhere('id', $storeId);
            return $store ? $store->pivot->quantity : 0;
        }
        
        return $product->stores->sum(function($store) {
            return $store->pivot->quantity ?? 0;
        });
    }
    
    /**
     * Générer le HTML pour le PDF
     */
    private function generatePdfHtml($products, $includeImages = false)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Export Produits</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
                h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #4CAF50; color: white; padding: 10px; text-align: left; }
                td { padding: 8px; border: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .product-image { width: 50px; height: 50px; object-fit: cover; }
                .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #666; }
                .no-image { color: #999; font-style: italic; }
            </style>
        </head>
        <body>
            <h1>Liste des produits</h1>
            <p>Date d\'export: ' . date('d/m/Y H:i:s') . '</p>
            <p>Total produits: ' . $products->count() . '</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>SKU</th>
                        <th>Catégories</th>
                        <th>Stock</th>
                        <th>Prix (F)</th>';
        
        if ($includeImages) {
            $html .= '<th>Image</th>';
        }
        
        $html .= '<th>Date</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($products as $product) {
            $quantity = $this->calculateQuantity($product);
            $categories = $product->categories->pluck('slug')->implode(', ');
            
            $html .= '<tr>
                        <td>' . htmlspecialchars($product->libelle) . '</td>
                        <td>' . htmlspecialchars($product->sku ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($categories) . '</td>
                        <td>' . htmlspecialchars($product->quantiteAvecUnite($quantity)) . '</td>
                        <td>' . number_format($product->price ?? 0, 0, ',', ' ') . ' F / ' . htmlspecialchars(Product::motUnite($product->unite)) . '</td>';
            
            if ($includeImages && $product->image) {
                $imagePath = public_path('products/' . $product->image);
                
                if (file_exists($imagePath)) {
                    // Lire l'image et la convertir en base64
                    $imageData = file_get_contents($imagePath);
                    $base64Image = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
                    
                    $html .= '<td><img src="' . $base64Image . '" class="product-image" alt="' . htmlspecialchars($product->libelle) . '"></td>';
                } else {
                    $html .= '<td class="no-image">Image non trouvée</td>';
                }
            }
            $html .= '<td>' . ($product->updated_at ? $product->updated_at->format('d/m/Y') : '') . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                Document généré le ' . date('d/m/Y H:i:s') . '
            </div>
        </body>
        </html>';
        
        return $html;
    }
}