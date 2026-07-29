<?php

namespace App\Filament\Client\Concerns;

use App\Models\ShipmentMedia;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

trait HasShipmentMediaAction
{
    /**
     * Read-only, stage-grouped photo/video gallery for the client portal — the same
     * data staff capture via the admin "Media / Evidence" action, but clients can only
     * view here, never upload.
     */
    public static function mediaAction(): Action
    {
        return Action::make('media')
            ->label('Photos & Updates')
            ->icon('heroicon-m-camera')
            ->color('purple')
            ->modalWidth('5xl')
            ->modalHeading('Shipment Photos & Updates')
            ->modalSubmitAction(false)
            ->form([
                Placeholder::make('media_gallery')
                    ->label('')
                    ->content(function ($record) {
                        $byStage = $record->media()->latest()->get()->groupBy('stage');

                        if ($byStage->isEmpty()) {
                            return 'No photos or videos have been uploaded for this shipment yet.';
                        }

                        $html = '';
                        foreach (ShipmentMedia::STAGES as $stage => $label) {
                            $items = $byStage->get($stage);
                            if (! $items || $items->isEmpty()) {
                                continue;
                            }

                            $html .= '<div class="mb-4"><div class="font-semibold text-sm mb-2">'.e($label).'</div>';
                            $html .= '<div class="grid grid-cols-3 gap-4">';
                            foreach ($items as $item) {
                                $html .= '<div class="border rounded-lg p-2 dark:border-gray-700">';
                                if ($item->type === 'image') {
                                    $html .= '<img src="'.asset('storage/'.$item->file_path).'" class="w-full h-32 object-cover rounded mb-2" />';
                                } else {
                                    $html .= '<video src="'.asset('storage/'.$item->file_path).'" controls class="w-full h-32 rounded mb-2"></video>';
                                }
                                if ($item->caption) {
                                    $html .= '<p class="text-xs text-gray-500">'.e($item->caption).'</p>';
                                }
                                $html .= '</div>';
                            }
                            $html .= '</div></div>';
                        }

                        return new HtmlString($html);
                    })
                    ->columnSpanFull(),
            ]);
    }
}
