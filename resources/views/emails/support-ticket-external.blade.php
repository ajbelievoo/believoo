@component('mail::message')
# New {{ $data['platform'] }} Support Ticket

A new support ticket has been created on the unified support portal.

**Platform:** {{ $data['platform'] }}
**Name:** {{ $data['name'] }}
**Email:** {{ $data['email'] }}
**Subject:** {{ $data['subject'] }}
**Priority:** {{ ucfirst($data['priority']) }}

**Message:**
{{ $data['message'] }}

@if(!empty($data['url']))
**External ticket link:** [Open in {{ $data['platform'] }}]({{ $data['url'] }})
@endif

Thanks,
Believoo Support
@endcomponent
