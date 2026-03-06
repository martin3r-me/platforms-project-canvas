<?php

use Platform\ProjectCanvas\Http\Controllers\CanvasPdfController;
use Platform\ProjectCanvas\Livewire\Canvas\Index;
use Platform\ProjectCanvas\Livewire\Canvas\Show;

Route::get('/', Index::class)->name('project-canvas.dashboard');
Route::get('/canvases', Index::class)->name('project-canvas.canvases.index');
Route::get('/canvases/{canvas}', Show::class)->name('project-canvas.canvases.show');
Route::get('/canvases/{canvas}/pdf', CanvasPdfController::class)->name('project-canvas.canvases.pdf');
