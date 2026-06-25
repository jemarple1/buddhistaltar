@if ($dedicationNames->isEmpty())
    Dedicated toward all butter lamp offerings.
@else
    Dedicated toward all butter lamp offerings, including {{ $dedicationNames->join(', ', ' and ') }}.
@endif
