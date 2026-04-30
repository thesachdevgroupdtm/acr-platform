@if($mslots->count())
    <div class="time-slot-section">
        <h4 class="time-slot-title"><span class="time-slot-badge">Morning</span> Morning Slot</h4>
        <div class="time-slots-grid">
            @foreach($mslots as $slot)
                <button type="button" class="time-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</button>
            @endforeach
        </div>
    </div>
@endif
@if($aslots->count())
    <div class="time-slot-section">
        <h4 class="time-slot-title"><span class="time-slot-badge">Afternoon</span> Afternoon Slot</h4>
        <div class="time-slots-grid">
            @foreach($aslots as $slot)
                <button type="button" class="time-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</button>
            @endforeach
        </div>
    </div>
@endif
@if($eslots->count())
    <div class="time-slot-section">
        <h4 class="time-slot-title"><span class="time-slot-badge">Evening</span> Evening Slot</h4>
        <div class="time-slots-grid">
            @foreach($eslots as $slot)
                <button type="button" class="time-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</button>
            @endforeach
        </div>
    </div>
@endif
@if($mslots->count() == 0 && $aslots->count() == 0 && $eslots->count() == 0)
    <div class="empty-slots-message">
        <i class="fas fa-calendar-times"></i>
        <h4>No slots available</h4>
        <p>Please choose another date for service booking</p>
    </div>
    
    <style>
    .empty-slots-message {
        text-align: center;
        padding: 40px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        color: var(--gray-medium);
        margin: 20px 0;
    }
    .empty-slots-message i {
        font-size: 52px;
        margin-bottom: 18px;
        color: #ced4da;
    }
    .empty-slots-message h4 {
        margin-bottom: 12px;
        font-weight: 600;
        color: var(--dark-color);
    }
    </style>
@endif