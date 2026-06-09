<div class="catalog-combination-set-editor">
    @if ($message)
        <div class="content-album-message catalog-combination-set-editor-message is-{{ $messageLevel }}" data-flash-message>
            <span>{{ $message }}</span>
            <button type="button" wire:click="$set('message', null)" aria-label="{{ __('Sluiten') }}">
                <x-admin.material-icon name="close" />
            </button>
        </div>
    @endif

    <form id="catalog-combination-set-form" wire:submit.prevent="save">
        <div class="response-mail-builder-toolbar catalog-combination-set-toolbar">
            <div class="response-mail-builder-title">
                <h2 class="title">{{ __('Combination set') }}</h2>
                <span>{{ trans_choice('{0} Geen producten geselecteerd|{1} :count product geselecteerd|[2,*] :count producten geselecteerd', count($productIds), ['count' => count($productIds)]) }}</span>
            </div>
            <div class="response-mail-builder-actions">
                <button class="btn btn-save" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <x-admin.material-icon name="save" />
                    <span wire:loading.remove wire:target="save">{{ __('Combination set opslaan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Opslaan...') }}</span>
                </button>
            </div>
        </div>

        <div class="catalog-combination-set-editor-grid">
            <aside class="response-mail-tag-panel catalog-combination-set-help" aria-labelledby="catalog-combination-set-help-title">
                <h2 id="catalog-combination-set-help-title" class="title">{{ __('Structuur') }}</h2>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Set') }}</h3>
                    <p>{{ __('Een set groepeert producten die als combinatie bij elkaar horen.') }}</p>
                </div>
                <div class="response-mail-tag-group">
                    <h3>{{ __('Producten') }}</h3>
                    <p>{{ __('Selecteer onder de set welke catalogusproducten erbij horen.') }}</p>
                </div>
            </aside>

            <div class="response-mail-list catalog-combination-set-list">
                <article class="response-mail-card catalog-combination-set-card">
                    <header class="response-mail-card-header">
                        <div>
                            <span class="response-mail-card-kicker">{{ __('Set') }}</span>
                            <h3>{{ filled($name) ? $name : __('Combination set') }}</h3>
                        </div>
                    </header>

                    <div class="response-mail-card-grid">
                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog-combination-set-name">{{ __('Naam') }}</label>
                            </div>
                            <div class="form-item-input">
                                <input id="catalog-combination-set-name" type="text" wire:model.live.debounce.500ms="name">
                                @error('name')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-item">
                            <div class="form-item-label">
                                <label for="catalog-combination-set-status">{{ __('Status') }}</label>
                            </div>
                            <div class="form-item-input">
                                <select id="catalog-combination-set-status" wire:model.live="status">
                                    <option value="active">{{ __('Actief') }}</option>
                                    <option value="inactive">{{ __('Inactief') }}</option>
                                </select>
                                @error('status')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="form-item-label">
                            <label for="catalog-combination-set-description">{{ __('Omschrijving') }}</label>
                        </div>
                        <div class="form-item-input">
                            <textarea id="catalog-combination-set-description" wire:model.live.debounce.500ms="description"></textarea>
                            @error('description')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <section class="catalog-combination-set-products" aria-label="{{ __('Producten in set') }}">
                        <div class="catalog-combination-set-products-header">
                            <h4>{{ __('Producten') }}</h4>
                            @error('product_ids')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="catalog-combination-product-list">
                            @foreach ($products as $product)
                                <button
                                    class="catalog-combination-product-option @if (in_array($product['id'], $productIds, true)) is-selected @endif"
                                    type="button"
                                    wire:click="toggleProduct({{ $product['id'] }})"
                                >
                                    <span class="catalog-combination-product-check">
                                        <x-admin.material-icon :name="in_array($product['id'], $productIds, true) ? 'check_box' : 'check_box_outline_blank'" />
                                    </span>
                                    <span>
                                        <strong>{{ $product['name'] }}</strong>
                                        @if ($product['sku'])
                                            <small>{{ $product['sku'] }}</small>
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                </article>
            </div>
        </div>
    </form>
</div>
