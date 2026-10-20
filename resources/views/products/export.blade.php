{{-- resources/views/products/export.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Produits</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #3E4C21; color: white; padding: 5px; }
        td { padding: 3px; border: 1px solid #ddd; }
        .product-image { width: 50px; height: 50px; object-fit: cover; }
    </style>
</head>
<body>
    <h3>Liste des produits ({{ $dataTable->count() }})</h3>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>SKU</th>
                <th>Catégorie</th>
                <th>Stock</th>
                <th>Unité</th>
                <th>Prix par unité</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dataTable as $dataItem)
                @php
                    if (isset($userStoreId) && $userStoreId) {
                        $store = $dataItem->stores->firstWhere('id', $userStoreId);
                        $quantity = $store ? $store->pivot->quantity : 0;
                    } else {
                        $quantity = $dataItem->stores->sum('pivot.quantity');
                    }
                    $categories = $dataItem->categories->pluck('slug')->implode(', ');
                @endphp
                <tr>
                    <td>{{ $dataItem->libelle }}</td>
                    <td>{{ $dataItem->sku }}</td>
                    <td>{{ $categories }}</td>
                    <td>{{ $quantity }}</td>
                    <td>{{ $dataItem->unite_libelle }}</td>
                    <td>{{ number_format($dataItem->price ?? 0, 0, ',', ' ') }} F</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>