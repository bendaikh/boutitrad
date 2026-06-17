<x-admin-layout title="{{ isset($order) ? 'Modifier commande' : 'Nouvelle commande' }}">
    @php $editing = isset($order); @endphp
    <form
        method="POST"
        action="{{ $editing ? route('orders.update', $order) : route('orders.store') }}"
        class="admin-form-shell admin-order-create-form max-w-full mb-4 sm:mb-0"
        x-data="orderForm()"
    >
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="admin-order-create-body">
        {{-- Barre 1 --}}
        <div class="admin-order-form-bar">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-x-2 gap-y-2">
                <div>
                    <label for="order_date" class="admin-order-form-label">Date commande</label>
                    <input
                        type="date"
                        id="order_date"
                        name="order_date"
                        value="{{ old('order_date', $order?->created_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="admin-order-form-input"
                    >
                </div>
                <div>
                    <label class="admin-order-form-label">Réf bon de commande</label>
                    <input
                        type="text"
                        value="{{ $previewReference }}"
                        readonly
                        class="admin-order-form-readonly font-mono text-xs"
                    >
                </div>
                <div>
                    <label for="client_id" class="admin-order-form-label">Client *</label>
                    <select
                        id="client_id"
                        name="client_id"
                        x-model="clientId"
                        @change="onClientChange()"
                        class="admin-order-form-input font-mono text-xs"
                    >
                        <option value="">+ Nouveau client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id', $order?->client_id) == $client->id)>
                                {{ $client->formattedId() }} — {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-order-form-label">Nom client *</label>
                    <input
                        type="text"
                        name="client_name"
                        x-model="clientName"
                        :readonly="!isNewClient"
                        :required="isNewClient"
                        :class="isNewClient ? 'admin-order-form-input' : 'admin-order-form-readonly'"
                    >
                    @error('client_name')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-2 md:col-span-1 xl:col-span-2">
                    <label class="admin-order-form-label">Commercial</label>
                    @if($isCommercial)
                        <input type="hidden" name="commercial_id" value="{{ auth()->id() }}">
                        <input type="text" value="{{ auth()->user()->name }}" readonly class="admin-order-form-readonly">
                    @else
                        <select id="commercial_id" name="commercial_id" x-model="commercialId" class="admin-order-form-input">
                            <option value="">Auto</option>
                            @foreach($commercials as $commercial)
                                <option value="{{ $commercial->id }}" @selected(old('commercial_id', $order?->commercial_id) == $commercial->id)>
                                    {{ $commercial->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>

        {{-- Barre 2 --}}
        <div class="admin-order-form-bar" :class="cityOpen && 'relative z-[150]'">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-2 gap-y-2">
                <div class="relative" @click.outside="closeCityDropdown()">
                    <label for="city_search" class="admin-order-form-label">Ville livraison *</label>
                    <input type="hidden" name="city_id" :value="cityId">
                    <input
                        type="text"
                        id="city_search"
                        x-ref="cityInput"
                        x-model="cityQuery"
                        @focus="openCityDropdown($event)"
                        @click="openCityDropdown($event)"
                        @input="openCityDropdown(); onCityQueryInput()"
                        @keydown.escape.prevent="closeCityDropdown()"
                        @keydown.arrow-down.prevent="openCityDropdown()"
                        autocomplete="off"
                        placeholder="Ex : Casa, Rabat, Marrakech…"
                        required
                        class="admin-order-form-input"
                    >
                    <div
                        x-show="cityOpen"
                        x-cloak
                        x-bind:style="cityDropdownStyle"
                        class="admin-city-search-dropdown"
                    >
                        <p
                            x-show="cityQuery.trim().length < 2"
                            class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400"
                            x-text="cityResultsHint"
                        ></p>
                        <p
                            x-show="cityQuery.trim().length >= 2 && cityMatches.length === 0"
                            class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400"
                        >Aucune ville trouvée — essayez une autre orthographe</p>
                        <template x-for="city in filteredCities" :key="city.id">
                            <button
                                type="button"
                                class="admin-city-search-option"
                                @mousedown.prevent="selectCity(city)"
                                x-text="city.name"
                            ></button>
                        </template>
                        <p
                            x-show="cityQuery.trim().length >= 2 && cityMatches.length > 0"
                            class="admin-city-search-hint"
                            x-text="cityResultsHint"
                        ></p>
                    </div>
                    @error('city_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="client_phone" class="admin-order-form-label">Téléphone client *</label>
                    <input
                        type="text"
                        id="client_phone"
                        name="client_phone"
                        x-model="clientPhone"
                        :required="isNewClient || !clientId"
                        class="admin-order-form-input"
                        placeholder="06..."
                    >
                    @error('client_phone')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                @if(! $isCommercial)
                <div>
                    <label for="livreur_id" class="admin-order-form-label">Livreur</label>
                    <select id="livreur_id" name="livreur_id" class="admin-order-form-input">
                        <option value="">Non assigné</option>
                        @foreach($livreurs as $livreur)
                            <option value="{{ $livreur->id }}" @selected(old('livreur_id', $order?->livreur_id) == $livreur->id)>
                                {{ $livreur->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label for="payment_mode" class="admin-order-form-label">Mode de paiement</label>
                    <select id="payment_mode" name="payment_mode" x-model="paymentMode" class="admin-order-form-input">
                        <option value="">—</option>
                        @foreach($paymentModes as $mode)
                            <option value="{{ $mode->value }}" @selected(old('payment_mode', $order?->payment_mode?->value) === $mode->value)>
                                {{ $mode->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_mode')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-0.5">
                        <label for="client_address" class="admin-order-form-label mb-0">Adresse livraison *</label>
                        @if($isCommercial)
                            <label
                                x-show="!isNewClient && clientId"
                                x-cloak
                                class="inline-flex items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-400 cursor-pointer select-none"
                            >
                                <input
                                    type="checkbox"
                                    name="update_client_address"
                                    value="1"
                                    x-model="editClientAddress"
                                    @change="onEditAddressToggle()"
                                    class="rounded border-slate-300 dark:border-slate-600 text-brand-600 focus:ring-brand-500"
                                >
                                Modifier l'adresse du client
                            </label>
                        @endif
                    </div>
                    <input
                        type="text"
                        id="client_address"
                        name="client_address"
                        x-model="clientAddress"
                        :readonly="!canEditClientAddress"
                        :required="isNewClient || !clientId || editClientAddress"
                        :class="canEditClientAddress ? 'admin-order-form-input' : 'admin-order-form-readonly'"
                        placeholder="Quartier, rue, n°…"
                    >
                    <p x-show="!isNewClient && clientId && !editClientAddress" x-cloak class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Adresse enregistrée sur la fiche client. Cochez « Modifier l'adresse » pour la changer.
                    </p>
                    @error('client_address')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Barre 3 : articles --}}
        <div class="admin-order-form-section border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between gap-3 mb-2 flex-wrap">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Articles commandés</h2>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                        <input type="checkbox" x-model="showStock" class="rounded border-slate-300 dark:border-slate-600 text-brand-600 focus:ring-brand-500">
                        Afficher le stock
                    </label>
                    <button type="button" @click="addItem()" class="text-sm link-brand font-medium">+ Ajouter article</button>
                </div>
            </div>

            <div class="hidden sm:grid sm:grid-cols-12 gap-2 mb-1 px-0.5">
                <div class="sm:col-span-2 admin-order-form-label">Réf article</div>
                <div class="sm:col-span-2 admin-order-form-label">Désignation article</div>
                <div class="sm:col-span-2 admin-order-form-label text-center">Quantité</div>
                <div class="sm:col-span-2 admin-order-form-label text-right">Prix U</div>
                <div class="sm:col-span-1 admin-order-form-label text-right">Coût livraison</div>
                <div class="sm:col-span-2 admin-order-form-label text-right">Sous total</div>
                <div class="sm:col-span-1"></div>
            </div>
            <p x-show="showStock" x-cloak class="hidden sm:block text-[10px] text-slate-500 dark:text-slate-400 mb-2 px-0.5">
                Le stock restant se recalcule automatiquement selon la quantité saisie.
            </p>

            <template x-for="(item, index) in items" :key="index">
                <div class="grid grid-cols-12 gap-2 mb-2 items-end">
                    <div class="col-span-12 sm:col-span-2">
                        <label class="admin-order-form-label sm:hidden">Réf article</label>
                        <select
                            :name="'items['+index+'][product_id]'"
                            x-model="item.product_id"
                            @change="onProductChange(index)"
                            required
                            class="admin-order-form-input font-mono text-xs"
                        >
                            <option value="">Sélectionner...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->sku }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-2">
                        <label class="admin-order-form-label sm:hidden">Désignation article</label>
                        <input type="text" x-model="item.name" readonly class="admin-order-form-readonly">
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <label class="admin-order-form-label sm:hidden text-center">Quantité</label>
                        <input
                            type="number"
                            :name="'items['+index+'][quantity]'"
                            x-model="item.quantity"
                            min="1"
                            required
                            placeholder="Qté"
                            class="admin-order-form-input text-center tabular-nums"
                            :class="quantityInputClass(item)"
                        >
                        <p
                            x-show="showStock && item.product_id"
                            x-cloak
                            class="text-[10px] mt-0.5 text-center tabular-nums font-medium"
                            :class="stockHintClass(item)"
                        >
                            Dispo : <span x-text="formatStock(item.stock)"></span>
                            · Reste : <span x-text="formatStock(remainingStock(item))"></span>
                            <span x-show="exceedsStock(item)"> — dépasse le stock</span>
                        </p>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <label class="admin-order-form-label sm:hidden text-right">Prix U</label>
                        <input
                            type="number"
                            :name="'items['+index+'][unit_price]'"
                            x-model.number="item.unit_price"
                            min="0"
                            step="0.01"
                            required
                            class="admin-order-form-input text-right tabular-nums"
                        >
                    </div>
                    <div class="col-span-6 sm:col-span-1" x-show="index === 0">
                        <div>
                            <label for="delivery_cost" class="admin-order-form-label sm:hidden text-right">Coût livraison</label>
                            <input
                                type="number"
                                id="delivery_cost"
                                name="delivery_cost"
                                x-model="deliveryCost"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                class="admin-order-form-input text-right tabular-nums"
                            >
                            @error('delivery_cost')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="hidden sm:block sm:col-span-1" x-show="index !== 0" aria-hidden="true"></div>
                    <div class="col-span-10 sm:col-span-2">
                        <label class="admin-order-form-label sm:hidden text-right">Sous total</label>
                        <input
                            type="text"
                            :value="formatMoney(lineSubtotal(item))"
                            readonly
                            class="admin-order-form-readonly text-right tabular-nums font-medium"
                        >
                    </div>
                    <div class="col-span-2 sm:col-span-1 flex justify-end">
                        <button
                            type="button"
                            @click="removeItem(index)"
                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-md"
                            title="Supprimer la ligne"
                        >&times;</button>
                    </div>
                </div>
            </template>

            <div class="flex flex-col items-end gap-1 pt-2 border-t border-slate-100 dark:border-slate-700 mt-2 text-sm text-slate-700 dark:text-slate-200">
                <div class="tabular-nums">
                    Sous-total articles : <span x-text="formatMoney(itemsSubtotal())"></span>
                </div>
                <div class="tabular-nums">
                    Coût livraison : <span x-text="formatMoney(deliveryCostAmount())"></span>
                </div>
                <div class="font-semibold text-slate-800 dark:text-slate-100 tabular-nums">
                    Total : <span x-text="formatMoney(orderTotal())"></span>
                </div>
            </div>

            @error('items')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            @error('items.*.product_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        </div>

        <div class="admin-order-form-actions">
            @if($isCommercial && ! $editing)
                <button type="submit" name="submit_action" value="submit" class="btn-primary bg-emerald-600 hover:bg-emerald-700 border-emerald-600">Créer et envoyer à l'admin</button>
                <button type="submit" name="submit_action" value="draft" class="btn-secondary">Enregistrer brouillon</button>
                <a href="{{ route('orders.index') }}" class="btn-secondary">Annuler</a>
            @elseif($editing)
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                <a href="{{ route('orders.index') }}" class="btn-secondary">Annuler</a>
            @else
                <button type="submit" name="submit_action" value="draft" class="btn-primary">Créer la commande</button>
                <a href="{{ route('orders.index') }}" class="btn-secondary">Annuler</a>
            @endif
        </div>
    </form>

    @push('scripts')
    <script>
    function orderForm() {
        const clients = @json($clientsData);
        const cities = @json($citiesData);
        const products = @json($productsData);
        const initialItems = @json($initialItems ?? []);
        const oldClientName = @json(old('client_name', ''));
        const oldClientPhone = @json(old('client_phone', $order?->client?->phone ?? ''));
        const oldClientAddress = @json(old('client_address', $order?->client?->address ?? ''));
        const oldCityId = @json(old('city_id', $order?->client?->city_id ?? ''));
        const oldClientId = @json(old('client_id', $order?->client_id ?? ''));
        const isCommercialUser = @json($isCommercial);
        const editClientAddressDefault = @json((bool) old('update_client_address', false));

        function buildItem(data = {}) {
            const product = products.find(p => String(p.id) === String(data.product_id || ''));

            return {
                product_id: data.product_id ? String(data.product_id) : '',
                quantity: data.quantity ?? '',
                name: product?.name ?? '',
                unit_price: data.unit_price ?? product?.sale_price ?? 0,
                stock: product?.stock ?? 0,
            };
        }

        return {
            clientId: oldClientId ? String(oldClientId) : '',
            isNewClient: ! oldClientId,
            clientName: oldClientName,
            clientPhone: oldClientPhone,
            clientAddress: oldClientAddress,
            editClientAddress: editClientAddressDefault,
            get canEditClientAddress() {
                if (! isCommercialUser) {
                    return true;
                }

                return this.isNewClient || ! this.clientId || this.editClientAddress;
            },
            cityId: oldCityId ? String(oldCityId) : '',
            cityQuery: '',
            cityOpen: false,
            cityDropdownStyle: '',
            commercialId: @json(old('commercial_id', $order?->commercial_id ?? '')),
            paymentMode: @json(old('payment_mode', $order?->payment_mode?->value ?? '')),
            deliveryCost: @json(old('delivery_cost', $order?->delivery_cost ?? $defaultDeliveryCost)),
            showStock: true,
            items: initialItems.length ? initialItems.map(item => buildItem(item)) : [buildItem()],
            get cityMatches() {
                const q = this.normalizeCitySearch(this.cityQuery);
                if (q.length < 2) {
                    return [];
                }

                return cities.filter(c => this.normalizeCitySearch(c.name).includes(q));
            },
            get filteredCities() {
                return this.cityMatches;
            },
            get cityResultsHint() {
                const total = cities.length;
                const q = this.cityQuery.trim();

                if (q.length < 2) {
                    return total
                        ? `Recherche parmi ${total} villes Cathedis — tapez au moins 2 lettres`
                        : 'Aucune ville disponible — synchronisez Cathedis';
                }

                const matches = this.cityMatches.length;

                if (matches === 0) {
                    return '';
                }

                return matches === 1 ? '1 ville trouvée' : `${matches} villes trouvées`;
            },
            normalizeCitySearch(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            },
            init() {
                this.syncCityQueryFromId();
                if (this.clientId) {
                    this.onClientChange(false);
                } else if (this.cityId) {
                    this.onCityChange(false);
                }
                this.bindCityDropdownListeners();
            },
            bindCityDropdownListeners() {
                const reposition = () => {
                    if (this.cityOpen) {
                        this.updateCityDropdownPosition();
                    }
                };
                window.addEventListener('scroll', reposition, true);
                window.addEventListener('resize', reposition);
            },
            updateCityDropdownPosition() {
                const input = this.$refs.cityInput;
                if (! input) {
                    return;
                }
                const rect = input.getBoundingClientRect();
                const maxHeight = Math.max(240, Math.min(window.innerHeight * 0.7, 448, window.innerHeight - rect.bottom - 12));
                this.cityDropdownStyle = `top:${rect.bottom + 4}px;left:${rect.left}px;width:${rect.width}px;max-height:${maxHeight}px;`;
            },
            openCityDropdown(event = null) {
                this.cityOpen = true;
                this.$nextTick(() => this.updateCityDropdownPosition());
                if (event?.target?.select && this.cityQuery) {
                    event.target.select();
                }
            },
            closeCityDropdown() {
                this.cityOpen = false;
            },
            syncCityQueryFromId() {
                if (! this.cityId) {
                    return;
                }
                const city = cities.find(c => String(c.id) === String(this.cityId));
                if (city) {
                    this.cityQuery = city.name;
                }
            },
            selectCity(city) {
                this.cityId = String(city.id);
                this.cityQuery = city.name;
                this.closeCityDropdown();
                this.onCityChange();
            },
            onCityQueryInput() {
                const exact = cities.find(c => this.normalizeCitySearch(c.name) === this.normalizeCitySearch(this.cityQuery));
                if (exact) {
                    this.cityId = String(exact.id);
                    this.applyCityDeliveryCost();
                    return;
                }
                this.cityId = '';
            },
            onEditAddressToggle() {
                if (this.editClientAddress) {
                    return;
                }

                const client = clients.find(c => String(c.id) === String(this.clientId));
                this.clientAddress = client?.address || '';
            },
            onClientChange(updateDeliveryCost = true) {
                this.isNewClient = ! this.clientId;
                this.editClientAddress = false;

                if (this.isNewClient) {
                    if (updateDeliveryCost && ! oldClientName) {
                        this.clientName = '';
                    }
                    this.cityId = '';
                    this.cityQuery = '';
                    this.clientPhone = '';
                    this.clientAddress = '';
                    this.closeCityDropdown();
                    return;
                }

                const client = clients.find(c => String(c.id) === String(this.clientId));
                if (! client) {
                    this.clientName = '';
                    this.cityId = '';
                    this.cityQuery = '';
                    this.clientPhone = '';
                    this.clientAddress = '';
                    return;
                }

                this.clientName = client.name;
                this.clientPhone = client.phone || '';
                this.clientAddress = client.address || '';
                this.cityId = client.city_id ? String(client.city_id) : '';
                if (this.cityId) {
                    this.syncCityQueryFromId();
                } else {
                    this.cityQuery = '';
                }
                if (client.commercial_id && ! this.commercialId) {
                    this.commercialId = String(client.commercial_id);
                }
                if (client.payment_mode) {
                    this.paymentMode = client.payment_mode;
                }
                if (updateDeliveryCost) {
                    this.applyCityDeliveryCost();
                }
            },
            onCityChange(updateDeliveryCost = true) {
                if (updateDeliveryCost) {
                    this.applyCityDeliveryCost();
                }
            },
            applyCityDeliveryCost() {
                const city = cities.find(c => String(c.id) === String(this.cityId));
                if (city && city.delivery_cost > 0) {
                    this.deliveryCost = city.delivery_cost;
                }
            },
            onProductChange(index) {
                const item = this.items[index];
                const product = products.find(p => String(p.id) === String(item.product_id));
                if (! product) {
                    item.name = '';
                    item.unit_price = 0;
                    item.stock = 0;
                    return;
                }
                item.name = product.name;
                item.unit_price = product.sale_price;
                item.stock = product.stock;
            },
            lineSubtotal(item) {
                const qty = Number(item.quantity) || 0;
                const price = Number(item.unit_price) || 0;
                return qty * price;
            },
            itemsSubtotal() {
                return this.items.reduce((sum, item) => sum + this.lineSubtotal(item), 0);
            },
            deliveryCostAmount() {
                return Number(this.deliveryCost) || 0;
            },
            orderTotal() {
                return this.itemsSubtotal() + this.deliveryCostAmount();
            },
            formatMoney(value) {
                const amount = Number(value) || 0;
                return new Intl.NumberFormat('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(amount) + ' DH';
            },
            formatStock(value) {
                const qty = Number(value) || 0;
                return new Intl.NumberFormat('fr-FR', {
                    maximumFractionDigits: 0,
                }).format(qty);
            },
            orderedQuantityForProduct(productId) {
                if (! productId) {
                    return 0;
                }

                return this.items
                    .filter(i => String(i.product_id) === String(productId))
                    .reduce((sum, i) => sum + (Number(i.quantity) || 0), 0);
            },
            remainingStock(item) {
                const stock = Number(item.stock) || 0;
                const ordered = this.orderedQuantityForProduct(item.product_id);

                return stock - ordered;
            },
            exceedsStock(item) {
                const stock = Number(item.stock) || 0;

                return this.orderedQuantityForProduct(item.product_id) > stock;
            },
            stockHintClass(item) {
                const remaining = this.remainingStock(item);

                if (this.exceedsStock(item) || remaining < 0) {
                    return 'text-red-600 dark:text-red-400';
                }
                if (remaining === 0) {
                    return 'text-amber-600 dark:text-amber-400';
                }
                if (remaining <= 5) {
                    return 'text-amber-600 dark:text-amber-400';
                }

                return 'text-emerald-600 dark:text-emerald-400';
            },
            quantityInputClass(item) {
                if (! this.showStock || ! item.product_id) {
                    return '';
                }

                if (this.exceedsStock(item)) {
                    return 'border-red-400 dark:border-red-500 ring-1 ring-red-200 dark:ring-red-900/40';
                }

                return '';
            },
            addItem() {
                this.items.push(buildItem());
            },
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
        };
    }
    </script>
    @endpush
</x-admin-layout>
