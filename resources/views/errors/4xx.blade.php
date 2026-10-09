{{-- Any 4xx status without a page of its own (405, 410, 413, …). --}}
<x-site.error-page code="4xx" :status="$exception->getStatusCode()"/>
