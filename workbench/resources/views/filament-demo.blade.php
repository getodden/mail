<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Odden Mail editor in Filament</title>
    @filamentStyles
    <style>body { margin: 0; padding: 16px; font-family: system-ui, sans-serif; background: #fff; }</style>
</head>
<body>
    @livewire('workbench-filament-demo')

    @livewireScripts
    @filamentScripts
</body>
</html>
