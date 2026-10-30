{{-- Qui finance cet achat : fonds propres, ou le fonds d'un associé. La
     marchandise reçue lui appartiendra, même rangée avec le reste ; les frais
     de l'arrivage sont avancés par le patron (dette de l'associé). --}}
@php $fondsChoisi = old('fund_id', $selectedFundId ?? ''); @endphp
<div class="col-lg-3 col-md-4 col-sm-6 col-12">
    <div class="form-group">
        <label>Financé par</label>
        <select name="fund_id" class="form-control">
            <option value="">{{ \App\Models\Fund::OWNER_LABEL }}</option>
            @foreach ($funds as $fund)
                <option value="{{ $fund->id }}" {{ (string) $fondsChoisi === (string) $fund->id ? 'selected' : '' }}>{{ $fund->name }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block">Associé : seul le prix des marchandises est prélevé sur son compte. Transport, douane et frais sont payés par le patron et inscrits comme dette de l'associé.</small>
        @error('fund_id') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
</div>
