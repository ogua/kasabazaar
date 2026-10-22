<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Client;
use App\Models\Shipment;
use App\Models\ShipmentMedia;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Media & Evidence library: every photo, video and document uploaded against the
 * current branch's shipments, shown as one album per client per container. Opening
 * an album lists all of its uploads, each viewable in a detail lightbox.
 */
class MediaEvidence extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static string $view = 'filament.pages.media-evidence';

    protected static ?string $title = 'Media & Evidence';

    protected static ?string $navigationLabel = 'Media & Evidence';

    protected static ?string $slug = 'media-evidence';

    protected static ?int $navigationSort = 2;

    public const PER_PAGE = 24;

    #[Url(except: '')]
    public ?string $search = '';

    #[Url(except: '')]
    public ?string $container = '';

    #[Url(except: '')]
    public ?string $client = '';

    #[Url(except: '')]
    public ?string $shipment = '';

    #[Url(except: '')]
    public ?string $stage = '';

    #[Url(except: '')]
    public ?string $type = '';

    #[Url(except: '')]
    public ?string $from = '';

    #[Url(except: '')]
    public ?string $until = '';

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    /**
     * Ids of media ticked for bulk removal.
     *
     * @var array<int, string>
     */
    public array $selected = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any_shipment') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return 'New';
    }

    public function mount(): void
    {
        $this->form->fill([
            'search' => $this->search,
            'container' => $this->container,
            'client' => $this->client,
            'shipment' => $this->shipment,
            'stage' => $this->stage,
            'from' => $this->from,
            'until' => $this->until,
            'sort' => $this->sort,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('search')
                    ->hiddenLabel()
                    ->placeholder('Search tracking no., reference, client, caption…')
                    ->prefixIcon('heroicon-m-magnifying-glass')
                    ->live(debounce: 400)
                    ->columnSpan(['default' => 1, 'sm' => 2]),

                Forms\Components\Select::make('container')
                    ->hiddenLabel()
                    ->placeholder('All containers')
                    ->options(fn (): array => $this->containerOptions())
                    ->searchable()
                    ->live(),

                Forms\Components\Select::make('client')
                    ->hiddenLabel()
                    ->placeholder('All clients')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => $this->tenantClients()
                        ->where(fn (Builder $query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (?string $value): ?string => $this->tenantClients()->whereKey($value)->value('name'))
                    ->options(fn (): array => $this->tenantClients()
                        ->whereHas('shipments.media')
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->live(),

                Forms\Components\Select::make('stage')
                    ->hiddenLabel()
                    ->placeholder('All stages')
                    ->options(ShipmentMedia::STAGES)
                    ->live(),

                Forms\Components\Select::make('sort')
                    ->hiddenLabel()
                    ->options(['newest' => 'Newest first', 'oldest' => 'Oldest first'])
                    ->selectablePlaceholder(false)
                    ->live(),

                Forms\Components\DatePicker::make('from')
                    ->hiddenLabel()
                    ->placeholder('Uploaded from')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->closeOnDateSelection()
                    ->live(),

                Forms\Components\DatePicker::make('until')
                    ->hiddenLabel()
                    ->placeholder('Uploaded until')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->closeOnDateSelection()
                    ->minDate(fn (Forms\Get $get) => $get('from') ?: null)
                    ->live(),

                Forms\Components\Hidden::make('shipment'),
            ])
            ->columns(['default' => 1, 'sm' => 2, 'lg' => 4]);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'container', 'client', 'shipment', 'stage', 'type', 'from', 'until', 'sort'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function setType(string $type): void
    {
        $this->type = array_key_exists($type, ShipmentMedia::TYPES) ? $type : '';
        $this->updated('type');
    }

    /**
     * Tick every file in the current page's albums that the user may remove.
     */
    public function selectPage(): void
    {
        $user = auth()->user();

        $this->selected = $this->getAlbums()->getCollection()
            ->flatMap(fn (array $album) => $album['media'])
            ->filter(fn (ShipmentMedia $item) => $item->isRemovableBy($user))
            ->pluck('id')
            ->all();
    }

    /**
     * Narrow the gallery from the lightbox ("show everything for this client / container / shipment").
     */
    public function focusOn(string $filter, string $value): void
    {
        if (! in_array($filter, ['client', 'container', 'shipment'], true)) {
            return;
        }

        $this->{$filter} = $value;
        $this->updated($filter);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'container', 'client', 'shipment', 'stage', 'type', 'from', 'until', 'sort']);
        $this->updated('search');
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->search) || filled($this->container) || filled($this->client) || filled($this->shipment)
            || filled($this->stage) || filled($this->type) || filled($this->from) || filled($this->until);
    }

    /**
     * Media belonging to the current branch's shipments, before any filter.
     *
     * @return Builder<ShipmentMedia>
     */
    public function tenantMediaQuery(): Builder
    {
        return ShipmentMedia::query()
            ->whereHas('shipment', fn (Builder $query) => $query->where('branch_id', Filament::getTenant()?->getKey()));
    }

    /**
     * @return Builder<ShipmentMedia>
     */
    protected function filteredQuery(bool $applyType = true): Builder
    {
        $search = trim((string) $this->search);

        return $this->tenantMediaQuery()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('shipment_media.caption', 'like', "%{$search}%")
                ->orWhere('shipment_media.file_path', 'like', "%{$search}%")
                ->orWhereHas('shipment', fn (Builder $query) => $query
                    ->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('shipping_reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")))))
            ->when(filled($this->container), fn (Builder $query) => $query->whereHas('shipment', fn (Builder $query) => $query->where('container_number', $this->container)))
            ->when(filled($this->client), fn (Builder $query) => $query->whereHas('shipment', fn (Builder $query) => $query->where('client_id', $this->client)))
            ->when(filled($this->shipment), fn (Builder $query) => $query->where('shipment_media.shipment_id', $this->shipment))
            ->when(filled($this->stage), fn (Builder $query) => $query->where('shipment_media.stage', $this->stage))
            ->when($applyType && filled($this->type), fn (Builder $query) => $query->where('shipment_media.type', $this->type))
            ->when(filled($this->from), fn (Builder $query) => $query->whereDate('shipment_media.created_at', '>=', $this->from))
            ->when(filled($this->until), fn (Builder $query) => $query->whereDate('shipment_media.created_at', '<=', $this->until));
    }

    /**
     * One album per client per container (a client's shipments without a container
     * share one "no container" album), each carrying every matching file.
     *
     * @return LengthAwarePaginator<array{key: string, media: Collection<int, ShipmentMedia>}>
     */
    public function getAlbums(): LengthAwarePaginator
    {
        $oldestFirst = $this->sort === 'oldest';

        $albums = $this->filteredQuery()
            ->toBase()
            ->join('shipments', 'shipments.id', '=', 'shipment_media.shipment_id')
            ->selectRaw("shipments.client_id as album_client, NULLIF(shipments.container_number, '') as album_container")
            ->selectRaw('max(shipment_media.created_at) as latest_at, min(shipment_media.created_at) as first_at')
            ->groupBy('album_client', 'album_container')
            ->orderBy($oldestFirst ? 'first_at' : 'latest_at', $oldestFirst ? 'asc' : 'desc')
            ->paginate(self::PER_PAGE);

        if ($albums->isEmpty()) {
            return $albums;
        }

        $media = $this->filteredQuery()
            ->whereHas('shipment', fn (Builder $query) => $query->where(function (Builder $query) use ($albums): void {
                foreach ($albums->getCollection() as $album) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->when(
                            filled($album->album_client),
                            fn (Builder $query) => $query->where('client_id', $album->album_client),
                            fn (Builder $query) => $query->whereNull('client_id'),
                        )
                        ->when(
                            filled($album->album_container),
                            fn (Builder $query) => $query->where('container_number', $album->album_container),
                            fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('container_number')->orWhere('container_number', '')),
                        ));
                }
            }))
            ->with(['shipment.client', 'shipment.receivers', 'uploader'])
            ->orderBy('shipment_media.created_at', $oldestFirst ? 'asc' : 'desc')
            ->get()
            ->groupBy(fn (ShipmentMedia $item): string => $this->albumKey($item->shipment?->client_id, $item->shipment?->container_number));

        return $albums->setCollection($albums->getCollection()->map(function (object $album) use ($media): array {
            $key = $this->albumKey($album->album_client, $album->album_container);

            return ['key' => $key, 'media' => $media->get($key, collect())];
        }));
    }

    protected function albumKey(mixed $clientId, mixed $containerNumber): string
    {
        return ($clientId ?? '').'|'.(filled($containerNumber) ? $containerNumber : '');
    }

    /**
     * What an album card and its file grid show.
     *
     * @param  array{key: string, media: Collection<int, ShipmentMedia>}  $album
     * @return array<string, mixed>
     */
    public function describeAlbum(array $album): array
    {
        $items = $album['media']->map(fn (ShipmentMedia $item) => $this->describe($item))->values();
        $first = $items->first();

        return [
            'key' => $album['key'],
            'clientId' => $first['clientId'] ?? null,
            'clientName' => $first['clientName'] ?? 'Unknown client',
            'clientPhone' => $first['clientPhone'] ?? null,
            'containerNumber' => $first['containerNumber'] ?? null,
            'container' => $first['container'] ?? null,
            'fileCount' => $items->count(),
            'shipmentCount' => $items->pluck('shipmentId')->filter()->unique()->count(),
            'typeCounts' => collect(ShipmentMedia::TYPES)
                ->mapWithKeys(fn (string $label, string $type) => [$type => $items->where('type', $type)->count()])
                ->all(),
            'latestDate' => $first['date'] ?? null,
            'latestAgo' => $first['ago'] ?? null,
            'cover' => $items->firstWhere('type', 'image') ?? $items->firstWhere('type', 'video') ?? $first,
            'items' => $items->all(),
        ];
    }

    /**
     * Counts per type for the current filters, used by the type tabs.
     *
     * @return array<string, int>
     */
    public function getTypeCounts(): array
    {
        $counts = $this->filteredQuery(applyType: false)
            ->toBase()
            ->selectRaw('shipment_media.type as type, count(*) as total')
            ->groupBy('shipment_media.type')
            ->pluck('total', 'type');

        return ['' => (int) $counts->sum()] + collect(ShipmentMedia::TYPES)
            ->mapWithKeys(fn (string $label, string $type) => [$type => (int) ($counts[$type] ?? 0)])
            ->all();
    }

    public function getFilteredShipment(): ?Shipment
    {
        return filled($this->shipment)
            ? Shipment::query()->where('branch_id', Filament::getTenant()?->getKey())->with('client')->find($this->shipment)
            : null;
    }

    /**
     * What the gallery tile and the lightbox show for one file.
     *
     * @return array<string, mixed>
     */
    public function describe(ShipmentMedia $item): array
    {
        $shipment = $item->shipment;
        $client = $shipment?->client;

        return [
            'id' => $item->id,
            'type' => $item->type,
            'url' => $item->url(),
            'fileName' => $item->fileName(),
            'extension' => strtoupper(pathinfo($item->file_path, PATHINFO_EXTENSION)),
            'size' => $item->fileSize(),
            'caption' => $item->caption,
            'stage' => $item->stage,
            'stageLabel' => $item->stageLabel(),
            'date' => $item->created_at?->format('D, d M Y'),
            'time' => $item->created_at?->format('h:i A'),
            'ago' => $item->created_at?->diffForHumans(),
            'uploader' => $item->uploader?->name ?? 'System / Unknown',
            'clientId' => $client?->id,
            'clientName' => $client?->name ?? 'Unknown client',
            'clientPhone' => $client?->phone,
            'clientEmail' => $client?->email,
            'receivers' => $shipment?->receivers->pluck('receiver_name')->filter()->unique()->values()->all() ?? [],
            'trackingNumber' => $shipment?->tracking_number,
            'reference' => $shipment?->shipping_reference,
            'containerNumber' => filled($shipment?->container_number) ? (string) $shipment->container_number : null,
            'container' => filled($shipment?->container_number) ? 'CON'.$shipment->container_number : null,
            'status' => $shipment?->status ? Str::headline((string) $shipment->status->getLabel()) : null,
            'shipmentId' => $shipment?->id,
            'shipmentUrl' => $shipment ? ShipmentResource::getUrl('edit', ['record' => $shipment]) : null,
            'removable' => $item->isRemovableBy(auth()->user()),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function containerOptions(): array
    {
        return Shipment::query()
            ->where('branch_id', Filament::getTenant()?->getKey())
            ->whereNotNull('container_number')
            ->where('container_number', '!=', '')
            ->whereHas('media')
            ->distinct()
            ->pluck('container_number')
            ->sortDesc(SORT_NATURAL)
            ->mapWithKeys(fn ($number) => [(string) $number => 'CON'.$number])
            ->all();
    }

    /**
     * @return Builder<Client>
     */
    protected function tenantClients(): Builder
    {
        return Client::query()->whereHas('shipments', fn (Builder $query) => $query->where('branch_id', Filament::getTenant()?->getKey()));
    }

    /**
     * @return Builder<Shipment>
     */
    protected function tenantShipments(): Builder
    {
        return Shipment::query()->where('branch_id', Filament::getTenant()?->getKey());
    }

    protected function shipmentLabel(Shipment $shipment): string
    {
        return collect([
            $shipment->tracking_number,
            $shipment->shipping_reference,
            $shipment->client?->name,
        ])->filter()->implode(' · ');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guide')
                ->label('How it works')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->slideOver()
                ->modalHeading('Media & Evidence — Guide')
                ->modalDescription('Everything staff need to know about the shipment evidence library.')
                ->modalContent(view('filament.pages.partials.media-evidence-guide'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            $this->uploadAction(),
        ];
    }

    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload Media')
            ->icon('heroicon-m-arrow-up-tray')
            ->modalHeading('Upload Media & Evidence')
            ->modalDescription('Attach photos, videos or paperwork to a shipment. Each file is stamped with the stage, your name and the upload time.')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Upload')
            ->fillForm(fn (): array => ['shipment_id' => $this->shipment ?: null])
            ->form([
                Forms\Components\Select::make('shipment_id')
                    ->label('Shipment')
                    ->placeholder('Search by tracking no., reference or client')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => $this->tenantShipments()
                        ->with('client')
                        ->where(fn (Builder $query) => $query
                            ->where('tracking_number', 'like', "%{$search}%")
                            ->orWhere('shipping_reference', 'like', "%{$search}%")
                            ->orWhereHas('client', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")))
                        ->latest()
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Shipment $shipment) => [$shipment->id => $this->shipmentLabel($shipment)])
                        ->all())
                    ->getOptionLabelUsing(function (?string $value): ?string {
                        $shipment = $this->tenantShipments()->with('client')->find($value);

                        return $shipment ? $this->shipmentLabel($shipment) : null;
                    })
                    ->exists('shipments', 'id', fn ($rule) => $rule->where('branch_id', Filament::getTenant()?->getKey())),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('stage')
                        ->label('Stage')
                        ->options(ShipmentMedia::STAGES)
                        ->default('pickup')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('caption')
                        ->label('Caption (optional)')
                        ->maxLength(500)
                        ->placeholder('e.g. Sealed boxes before loading'),
                ]),

                Forms\Components\FileUpload::make('files')
                    ->label('Photos / Videos / Documents')
                    ->multiple()
                    ->required()
                    ->disk('public')
                    ->directory('shipment-media')
                    ->acceptedFileTypes(ShipmentMedia::ACCEPTED_UPLOAD_TYPES)
                    ->maxSize(51200)
                    ->helperText('Up to 50 MB per file. Images, videos, PDF, Word, Excel, CSV and text files are accepted.'),
            ])
            ->action(function (array $data): void {
                foreach ($data['files'] as $filePath) {
                    ShipmentMedia::create([
                        'shipment_id' => $data['shipment_id'],
                        'type' => ShipmentMedia::typeForPath($filePath),
                        'file_path' => $filePath,
                        'stage' => $data['stage'],
                        'caption' => $data['caption'] ?? null,
                        'uploaded_by' => auth()->id(),
                    ]);
                }

                Notification::make()
                    ->title('Evidence Uploaded')
                    ->body(count($data['files']).' file(s) uploaded successfully.')
                    ->success()
                    ->send();
            });
    }

    public function deleteMediaAction(): Action
    {
        return Action::make('deleteMedia')
            ->requiresConfirmation()
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->modalHeading('Remove this file?')
            ->modalDescription(fn (array $arguments): string => 'The file "'.($this->tenantMediaQuery()->find($arguments['id'] ?? null)?->fileName() ?? 'unknown')
                .'" and its stored copy will be permanently deleted. This cannot be undone.')
            ->modalSubmitActionLabel('Remove')
            ->action(function (array $arguments): void {
                $removed = $this->removeMedia([$arguments['id'] ?? '']);

                $removed > 0
                    ? Notification::make()->title('Media Removed')->body('The file was removed.')->success()->send()
                    : Notification::make()->title('Not Removed')->body('You can only remove files you uploaded.')->danger()->send();
            });
    }

    public function deleteSelectedAction(): Action
    {
        return Action::make('deleteSelected')
            ->label(fn (): string => 'Remove selected ('.count($this->selected).')')
            ->requiresConfirmation()
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->size('sm')
            ->modalHeading('Remove selected files?')
            ->modalDescription(fn (): string => count($this->selected).' file(s) and their stored copies will be permanently deleted. Files you are not allowed to remove are skipped.')
            ->modalSubmitActionLabel('Remove')
            ->action(function (): void {
                $requested = count($this->selected);
                $removed = $this->removeMedia($this->selected);
                $this->selected = [];

                Notification::make()
                    ->title('Media Removed')
                    ->body($removed.' of '.$requested.' file(s) removed.'.($removed < $requested ? ' The rest were uploaded by other staff.' : ''))
                    ->status($removed > 0 ? 'success' : 'warning')
                    ->send();
            });
    }

    /**
     * Delete the given media (record and stored file) that the current user may remove.
     *
     * @param  array<int, string>  $ids
     */
    public function removeMedia(array $ids): int
    {
        $user = auth()->user();

        return $this->tenantMediaQuery()
            ->whereIn('id', array_filter($ids))
            ->get()
            ->filter(fn (ShipmentMedia $item) => $item->isRemovableBy($user))
            ->each(fn (ShipmentMedia $item) => $item->delete())
            ->count();
    }
}
