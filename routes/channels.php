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
