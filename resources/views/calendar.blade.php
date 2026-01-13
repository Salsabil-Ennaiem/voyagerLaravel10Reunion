@extends('voyager::master')

@section('page_title', 'Calendar')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-calendar"></i> Calendar
    </h1>
@endsection

@section('after_css')
<style>
    .calendar-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-top: 20px;
    }
    
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .calendar-nav {
        display: flex;
        gap: 10px;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e0e0e0;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .calendar-day-header {
        background: #f5f5f5;
        padding: 10px;
        text-align: center;
        font-weight: bold;
        font-size: 14px;
    }
    
    .calendar-day {
        background: #fff;
        min-height: 100px;
        padding: 8px;
        position: relative;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .calendar-day:hover {
        background: #f9f9f9;
    }
    
    .calendar-day.other-month {
        background: #fafafa;
        color: #ccc;
    }
    
    .calendar-day.today {
        background: #e3f2fd;
    }
    
    .calendar-day.has-meeting {
        background: #fff8e1;
    }
    
    .calendar-day.has-meeting::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 6px;
        height: 6px;
        background: #ff9800;
        border-radius: 50%;
    }
    
    .day-number {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .meeting-item {
        font-size: 11px;
        padding: 2px 5px;
        margin: 2px 0;
        background: #ffcc80;
        border-radius: 3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .filter-controls {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .filter-controls select {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    .meetings-list {
        margin-top: 30px;
    }

    .meeting-card {
        border-left: 4px solid #ff9800;
        padding: 10px;
        margin-bottom: 10px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="calendar-container">
                <!-- Calendar Filters -->
                <div class="filter-controls">
                    <form method="GET" action="{{ route('calendar') }}" class="d-flex gap-3 align-items-center">
                        <select name="year" class="form-select">
                            <option value="">All Years</option>
                            @foreach(range(date('Y') - 5, date('Y') + 5) as $y)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        
                        <select name="month" class="form-select">
                            <option value="">All Months</option>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                            @endforeach
                        </select>
                        
                        <select name="day" class="form-select">
                            <option value="">All Days</option>
                            @foreach(range(1,31) as $d)
                                <option value="{{ $d }}" {{ request('day') == $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endforeach
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('calendar') }}" class="btn btn-secondary">Reset</a>
                    </form>
                </div>

                <!-- Calendar Header -->
                <div class="calendar-header">
                    <h2>{{ $currentDate->format('F Y') }}</h2>
                    <div class="calendar-nav">
                        <a href="{{ route('calendar', [
                            'year' => $currentDate->copy()->subMonth()->year,
                            'month' => $currentDate->copy()->subMonth()->month,
                            'day' => request('day')
                        ]) }}" class="btn btn-sm btn-secondary">
                            <i class="voyager-angle-left"></i>
                        </a>
                        <a href="{{ route('calendar', [
                            'year' => $currentDate->copy()->addMonth()->year,
                            'month' => $currentDate->copy()->addMonth()->month,
                            'day' => request('day')
                        ]) }}" class="btn btn-sm btn-secondary">
                            <i class="voyager-angle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <!-- Day Headers -->
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    
                    <!-- Calendar Days -->
                    @foreach($calendarDays as $day)
                        <div class="calendar-day 
                            {{ $day->isCurrentMonth ? '' : 'other-month' }}
                            {{ $day->isToday ? 'today' : '' }}
                            {{ isset($meetingsByDate[$day->format('Y-m-d')]) ? 'has-meeting' : '' }}">
                            <div class="day-number">{{ $day->day }}</div>
                            @if(isset($meetingsByDate[$day->format('Y-m-d')]))
                                @foreach($meetingsByDate[$day->format('Y-m-d')] as $meeting)
                                    <div class="meeting-item" title="{{ $meeting->objet }}">
                                        {{ Str::limit($meeting->objet, 20) }}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Meetings List -->
            @if($filteredMeetings->count() > 0)
                <div class="meetings-list">
                    <h3>Meetings ({{ $filteredMeetings->count() }})</h3>
                    @foreach($filteredMeetings as $meeting)
                        <div class="meeting-card">
                            <div class="d-flex justify-content-between">
                                <h5>{{ $meeting->objet }}</h5>
                                <span class="badge bg-{{ $meeting->statut === 'confirmé' ? 'success' : 'warning' }}">
                                    {{ $meeting->statut }}
                                </span>
                            </div>
                            <p class="mb-1">{{ $meeting->description }}</p>
                            <small>
                                <i class="voyager-calendar"></i> 
                                {{ $meeting->date_debut->format('M d, Y H:i') }} - {{ $meeting->date_fin->format('H:i') }}
                                <br>
                                <i class="voyager-map"></i> {{ $meeting->lieu }}
                                <br>
                                <i class="voyager-user"></i> 
                                Président: {{ $meeting->president->name }}
                            </small>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('after_js')
<script>
    // Add any JavaScript interactions here
</script>
@endsection
