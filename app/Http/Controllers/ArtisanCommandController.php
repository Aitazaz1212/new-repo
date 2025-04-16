<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

final class ArtisanCommandController extends Controller
{
    private array $allowedCommands = [
        'cache:clear' => 'Clear Cache',
        'config:clear' => 'Clear Config',
        'view:clear' => 'Clear Views',
        'route:clear' => 'Clear Routes',
        'migrate:fresh --seed' => 'Fresh Migration with Seeders',
        'storage:link' => 'Create Storage Link',
        'queue:restart' => 'Restart Queue Workers',
    ];

    private function getAvailableSeeders(): array
    {
        $seederPath = database_path('seeders');
        $files = File::files($seederPath);

        return collect($files)
            ->map(function ($file) {
                // Get filename without extension
                return pathinfo($file->getFilename(), PATHINFO_FILENAME);
            })
            ->filter(function ($className) {
                // Filter out DatabaseSeeder and any non-php files
                return $className !== 'DatabaseSeeder';
            })
            ->values()
            ->toArray();
    }

    public function index(): View
    {
        return view('artisan.index', [
            'commands' => $this->allowedCommands,
            'seeders' => $this->getAvailableSeeders(),
            'output' => session('artisan_output'),
        ]);
    }

    public function execute(Request $request): RedirectResponse
    {
        $command = $request->input('command');
        $seeder = $request->input('seeder');

        // Handle seeder execution
        if ($seeder) {
            $availableSeeders = $this->getAvailableSeeders();
            if (!in_array($seeder, $availableSeeders)) {
                return back()->with('error', 'Seeder not allowed');
            }
            $command = "db:seed --class={$seeder}";
        }
        // Handle regular command execution
        elseif (!array_key_exists($command, $this->allowedCommands)) {
            return back()->with('error', 'Command not allowed');
        }

        try {
            $output = [];
            Artisan::call($command, [], $output);
            $outputText = implode("\n", $output);

            return back()->with('artisan_output', $outputText)
                ->with('success', "Command '{$command}' executed successfully");
        } catch (\Exception $e) {
            return back()->with('error', "Error executing command: {$e->getMessage()}");
        }
    }
}
