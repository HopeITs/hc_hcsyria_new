{{-- Any 5xx status without a page of its own (502, 504, …). --}}
<x-site.error-page code="5xx" :status="$exception->getStatusCode()"/>
