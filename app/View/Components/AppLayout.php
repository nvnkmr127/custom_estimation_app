<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $navigation = app(\App\Services\Navigation\NavigationService::class);
        return view('components.app-layout', [
            'sidebarMenu' => $navigation->getSidebarMenu(),
        ]);
    }
}
