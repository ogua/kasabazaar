<?php

namespace App\Filament\Client\Concerns;

use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

trait HasShipmentMediaAction
{
    /**
     * Read-only, stage-grouped photo/video/document gallery for the client portal — the
     * same data staff capture via the admin "Media / Evidence" action, but clients can
     * only view here, never upload or remove.
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
                    ->content(fn ($record) => new HtmlString(
                        view('filament.components.shipment-media-gallery', [
                            'media' => $record->media()->latest()->get(),
                            'emptyMessage' => 'No photos or videos have been uploaded for this shipment yet.',
                            'showMeta' => false,
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }
}
