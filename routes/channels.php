<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// User private channel for VM migration updates
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// VM specific channel for migration progress
Broadcast::channel('vm.{vmId}', function ($user, $vmId) {
    // Check if user owns this VM
    $vm = \App\Models\ProxmoxVm::where('id', $vmId)
        ->where('user_id', $user->id)
        ->first();
    
    return $vm !== null;
});

// Server Import specific channel
Broadcast::channel('import.{importId}', function ($user, $importId) {
    // Check if user owns this import
    $import = \App\Models\ServerImport::where('id', $importId)
        ->where('user_id', $user->id)
        ->first();
    
    return $import !== null;
});

// B-CONNECT public channels: restrict to active company members
Broadcast::channel('company.{companyId}.chat.{channelType}.{channelId}', function ($user, $companyId, $channelType, $channelId) {
    return \App\Models\Bconnect\Member::where('user_id', $user->id)
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->exists();
});

Broadcast::channel('company.{companyId}.whiteboard.{projectId}', function ($user, $companyId, $projectId) {
    return \App\Models\Bconnect\Member::where('user_id', $user->id)
        ->where('company_id', $companyId)
        ->where('is_active', true)
        ->exists();
});
