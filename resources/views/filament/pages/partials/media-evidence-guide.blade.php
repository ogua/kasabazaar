@php
    $sections = [
        [
            'icon' => 'heroicon-o-photo',
            'title' => 'What this page is',
            'body' => [
                'The Media & Evidence library collects every photo, video and document uploaded against this branch\'s shipments — from the shipment table\'s "Media / Evidence" action, the post-create "Upload Evidence" step, and the staff mobile app.',
                'It is the single place to prove the condition of goods at each step, answer customer disputes and audit what was handed over.',
            ],
        ],
        [
            'icon' => 'heroicon-o-funnel',
            'title' => 'Finding files',
            'body' => [
                '<strong>Search</strong> matches the tracking number, shipping reference, client name or phone, caption and file name.',
                '<strong>Container</strong> lists only containers that have media, e.g. CON49.',
                '<strong>Client</strong> — start typing a name or phone number to find any client of this branch.',
                '<strong>Stage</strong>, <strong>date range</strong> and <strong>sort order</strong> narrow things further.',
                'The four cards at the top are type tabs (All / Photos / Videos / Documents) and show counts for the current filters.',
                'Filters are kept in the address bar, so a filtered view can be bookmarked or shared with a colleague.',
            ],
        ],
        [
            'icon' => 'heroicon-o-eye',
            'title' => 'Viewing a file',
            'body' => [
                'Hover a tile to see the client, tracking number, container, upload date and time, uploader and caption.',
                'Click a tile to open the viewer. Use <kbd>←</kbd> / <kbd>→</kbd> to move between files on the page and <kbd>Esc</kbd> to close.',
                'The side panel shows full client details (tap to call or email), receiver, shipment status, stage, file size and who uploaded it.',
                'Photos and videos play inline, PDFs preview in the viewer; Word, Excel and other documents can be downloaded.',
                'Shortcut buttons show all media for the same client, container or shipment.',
            ],
        ],
        [
            'icon' => 'heroicon-o-arrow-up-tray',
            'title' => 'Uploading',
            'body' => [
                'Click <strong>Upload Media</strong>, search for the shipment, pick the stage and add an optional caption.',
                'You can add several files at once — up to 50 MB each. Accepted: images (JPG, PNG, WebP, HEIC…), videos (MP4, MOV…), PDF, Word, Excel, CSV and text.',
                'Every file is stamped with your name and the upload time automatically.',
            ],
        ],
        [
            'icon' => 'heroicon-o-trash',
            'title' => 'Removing files',
            'body' => [
                'Staff with the <em>Update Shipment</em> permission may remove any file. Everyone else may remove only files they uploaded themselves.',
                'Remove one file from the viewer, or tick several tiles (checkbox in the corner) and use <strong>Remove selected</strong>.',
                'Removal deletes the record and the stored file permanently — it cannot be undone. Download a copy first if in doubt.',
            ],
        ],
    ];

    $stages = [
        ['label' => 'At Pickup', 'class' => 'bg-amber-500', 'text' => 'Goods collected from the sender: condition, packaging, item count.'],
        ['label' => 'In Transit', 'class' => 'bg-sky-600', 'text' => 'Warehouse, loading, container stuffing and en-route checks.'],
        ['label' => 'At Delivery', 'class' => 'bg-emerald-600', 'text' => 'Hand-over to the receiver, signatures, waybills.'],
        ['label' => 'Post Delivery', 'class' => 'bg-purple-600', 'text' => 'Claims, damage reports and follow-up paperwork.'],
    ];
@endphp

<div class="flex flex-col gap-6 text-sm text-gray-600 dark:text-gray-300">
    @foreach ($sections as $section)
        <section class="flex gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <x-filament::icon :icon="$section['icon']" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <h3 class="mb-1.5 text-base font-semibold text-gray-950 dark:text-white">{{ $section['title'] }}</h3>
                <ul class="list-disc space-y-1 ps-4 marker:text-gray-300 dark:marker:text-gray-600">
                    @foreach ($section['body'] as $line)
                        <li>{!! $line !!}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endforeach

    <section class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <h3 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">Stages</h3>
        <dl class="flex flex-col gap-2.5">
            @foreach ($stages as $stage)
                <div class="flex items-start gap-3">
                    <dt class="w-28 shrink-0">
                        <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white {{ $stage['class'] }}">{{ $stage['label'] }}</span>
                    </dt>
                    <dd>{{ $stage['text'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="rounded-xl bg-warning-50 p-4 text-warning-800 ring-1 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-300 dark:ring-warning-400/30">
        <h3 class="mb-1 font-semibold">Good evidence practice</h3>
        <ul class="list-disc space-y-1 ps-4">
            <li>Photograph every side of a box and any existing damage before it leaves the sender.</li>
            <li>Include the shipping label or tracking number in at least one photo.</li>
            <li>Add a caption — "3 of 5 boxes, taped" is far more useful in a dispute than no caption.</li>
            <li>Choose the correct stage; it is what customers see on their tracking timeline.</li>
        </ul>
    </section>
</div>
