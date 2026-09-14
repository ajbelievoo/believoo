<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class StreamingSdkController extends Controller
{
    /**
     * Download Android SDK AAR file
     */
    public function downloadAndroidSdk()
    {
        // SDK file path
        $sdkPath = storage_path('app/public/sdk/believoo-live-sdk-2.0.0.aar');
        
        // Check if file exists, if not create a placeholder
        if (!file_exists($sdkPath)) {
            // Create directory if not exists
            if (!is_dir(dirname($sdkPath))) {
                mkdir(dirname($sdkPath), 0755, true);
            }
            
            // For demo purposes, we'll stream a generated SDK package
            return $this->generateSdkDownload('believoo-live-sdk-2.0.0.aar');
        }
        
        return response()->download($sdkPath, 'believoo-live-sdk-2.0.0.aar', [
            'Content-Type' => 'application/java-archive',
        ]);
    }
    
    /**
     * Download Flutter SDK
     */
    public function downloadFlutterSdk()
    {
        return $this->generateSdkDownload('believoo_live-2.0.0.zip');
    }
    
    /**
     * Download React SDK
     */
    public function downloadReactSdk()
    {
        return $this->generateSdkDownload('believoo-react-live-2.0.0.tgz');
    }
    
    /**
     * Get SDK download page with instructions
     */
    public function sdkDownloads()
    {
        return view('docs.sdk-downloads');
    }
    
    /**
     * Generate SDK download response
     */
    private function generateSdkDownload($filename)
    {
        // In production, this would serve the actual SDK file
        // For now, return instructions
        return response()->json([
            'message' => 'SDK package ready for download',
            'filename' => $filename,
            'download_url' => route('streaming.sdk.android'),
            'instructions' => 'Please contact support@believoo.com for immediate SDK access',
            'alternative' => [
                'maven_url' => 'https://maven.believoo.com/repository/releases/com/believoo/live-sdk/2.0.0/',
                'github_releases' => 'https://github.com/believoo/live-sdk/releases',
                'cdn_url' => 'https://cdn.believoo.com/sdk/android/believoo-live-sdk-2.0.0.aar'
            ]
        ]);
    }
    
    /**
     * Get SDK integration documentation
     */
    public function getIntegrationDocs($platform)
    {
        $docs = [
            'android' => [
                'gradle_dependency' => "implementation 'com.believoo:live-sdk:2.0.0'",
                'maven_repo' => 'https://maven.believoo.com/repository/releases',
                'min_sdk' => 21,
                'target_sdk' => 34,
                'permissions' => [
                    'android.permission.CAMERA',
                    'android.permission.RECORD_AUDIO',
                    'android.permission.INTERNET',
                    'android.permission.ACCESS_NETWORK_STATE'
                ]
            ],
            'flutter' => [
                'pubspec' => "believoo_live: ^2.0.0",
                'min_sdk' => 21,
            ],
            'react' => [
                'npm' => 'npm install @believoo/react-live',
                'yarn' => 'yarn add @believoo/react-live'
            ]
        ];
        
        return response()->json($docs[$platform] ?? ['error' => 'Platform not found']);
    }
}
