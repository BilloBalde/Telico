{{-- resources/views/exports/products-html.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Produits</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #3E4C21;
            padding-bottom: 10px;
            text-align: center;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #3E4C21;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-size: 14px;
        }
        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .no-image {
            color: #999;
            font-style: italic;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .print-button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px 20px;
            background-color: #3E4C21;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .print-button:hover {
            background-color: #45a049;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button">
        <i class="fa fa-print"></i> Télécharger en PDF
    </button>

    <h1>Liste des Produits</h1>
    
    <div class="header-info">
        <div>
            <strong>Date d'export:</strong> {{ now()->format('d/m/Y H:i:s') }}
        </div>
        <div>
            <strong>Total produits:</strong> {{ $products->count() }}
        </div>
        <div>
            <strong>Généré par:</strong> {{ auth()->user()->name ?? 'Système' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>SKU</th>
                <th>Catégories</th>
                <th>Stock</th>
                <th>Prix (F)</th>
                <th>Image</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            @php
                // Calculer la quantité selon le rôle
                if (auth()->check() && auth()->user()->isStoreScoped()) {
                    $storeId = auth()->user()->assignedStoreId();
                    $store = $product->stores->firstWhere('id', $storeId);
                    $quantity = $store ? $store->pivot->quantity : 0;
                } else {
                    $quantity = $product->stores->sum('pivot.quantity');
                }
                
                $categories = $product->categories->pluck('slug')->implode(', ');
            @endphp
            <tr>
                <td><strong>{{ $product->libelle }}</strong></td>
                <td>{{ $product->sku ?? 'N/A' }}</td>
                <td>{{ $categories ?: 'N/A' }}</td>
                <td class="text-center">{{ $product->quantiteAvecUnite($quantity) }}</td>
                <td class="text-right">{{ number_format($product->price ?? 0, 0, ',', ' ') }} F <small>/ {{ \App\Models\Product::motUnite($product->unite) }}</small></td>
                <td class="text-center">
                    @if($product->image)
                        <img src="{{ asset('products/' . $product->image) }}" 
                             alt="{{ $product->libelle }}" 
                             class="product-image"
                             onerror="this.style.display='none'; this.parentNode.innerHTML='<span class=\'no-image\'>Image non trouvée</span>';">
                    @else
                        <span class="no-image">-</span>
                    @endif
                </td>
                <td>{{ $product->updated_at ? $product->updated_at->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>TELICO - Tous droits réservés</p>
    </div>

    <script>
        // Remplacer le bouton d'impression par un bouton de téléchargement PDF
        document.querySelector('.print-button').addEventListener('click', function() {
            window.print();
        });
    </script>
</body>
</html>