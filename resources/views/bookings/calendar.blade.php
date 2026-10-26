@extends('layouts.app')
@section('title', 'Kalender Booking')
@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
<style>
    #calendar { min-height: 300px; }
    @media (max-width: 576px) {
        .fc .fc-header-toolbar { flex-wrap: wrap; gap: .5rem; }
        .fc .fc-toolbar-chunk { margin: .15rem 0; }
    }
</style>
@endpush
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-calendar-alt me-2"></i>Kalender Booking & Service</h4>
    <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-list me-1"></i>List View</a>
</div>
<div class="card"><div class="card-body">
    <div id="calendar"></div>
    <noscript><div class="alert alert-warning mb-0">Kalender membutuhkan JavaScript. <a href="{{ route('bookings.index') }}">Buka daftar booking</a>.</div></noscript>
</div></div>
@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var cal = new FullCalendar.Calendar(document.getElementById('calendar'),{
        initialView:'dayGridMonth',height:'auto',locale:'id',
        headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek,listWeek'},
        events:'{{ route("bookings.calendar.events") }}',
        eventClick:function(info){if(info.event.url){window.open(info.event.url);info.jsEvent.preventDefault();}}
    });
    cal.render();
});
</script>
@endpush
@endsection
