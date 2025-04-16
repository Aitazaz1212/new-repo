<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanCommandController extends Controller
{
    /**
     * Execute an Artisan command.
     */
    public function execute(Request $request): JsonResponse
    {
        $command = $request->input('command');
        $parameters = $request->input('parameters', []);

        // Validate command
        if (!in_array($command, config('artisan.allowed_commands'), true)) {
            return response()->json([
                'error' => 'Command not allowed'
            ], 403);
        }

        try {
            $output = new BufferedOutput;
            $exitCode = Artisan::call($command, $parameters, $output);
            
            return response()->json([
                'success' => true,
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output->fetch()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 