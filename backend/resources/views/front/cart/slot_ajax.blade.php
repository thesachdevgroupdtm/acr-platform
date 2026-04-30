@if($mslots->count())
    <section class="cart-page">
        <div class="container">
            <div class="table-responsive">
                <table class="table cart-page__table">
                    <thead>
                        <tr>
                            <th colspan="4"><span>Slots</span> Morning Slot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4">
                                <div class="row m-0">
                                    @foreach($mslots as $slot)
                                        <div class="col-12 col-sm-3">
                                            <a class="karoons-btn slot-btn morning-slot-btn" 
                                               data-id="{{$slot->time}}">
                                                <span>{{$slot->time}}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($aslots->count())
    <section class="cart-page">
        <div class="container">
            <div class="table-responsive">
                <table class="table cart-page__table">
                    <thead>
                        <tr>
                            <th colspan="4"><span>Slots</span> Afternoon Slot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4">
                                <div class="row m-0">
                                    @foreach($aslots as $slot)
                                        <div class="col-12 col-sm-3">
                                            <a class="karoons-btn slot-btn afternoon-slot-btn" 
                                               data-id="{{$slot->time}}">
                                                <span>{{$slot->time}}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($eslots->count())
    <section class="cart-page">
        <div class="container">
            <div class="table-responsive">
                <table class="table cart-page__table">
                    <thead>
                        <tr>
                            <th colspan="4"><span>Slots</span> Evening Slot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4">
                                <div class="row m-0">
                                    @foreach($eslots as $slot)
                                        <div class="col-12 col-sm-3">
                                            <a class="karoons-btn slot-btn evening-slot-btn" 
                                               data-id="{{$slot->time}}">
                                                <span>{{$slot->time}}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif
