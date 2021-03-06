@extends('app')

@section('content')
  <div class="page-header {{ $charter->isDeleted() ? 'deleted' : '' }}">
    <h1><a href="{{ route('leagues.show', [ $league->slug ] ) }}">{{ $league->name }}</a> - {{ $charter->name }} <small>({{ $charter->charter_type->name }})</small></h1>
  </div>

  @if( !$charter->active_from && ( $reason = $charter->rejection_reason ) )
    <p>Charter submission was rejected: {{ $reason }}</p>
  @endif

  @if( !$charter->active_from && !$charter->approval_requested_at && !$charter->isDeleted() )
    @if( Auth::user()->can('charter-request_approval') && ( ( Auth::user()->id == $league->user_id ) || Auth::user()->hasRole('root') ) )
      {!! Form::model( $charter, ['method' => 'patch', 'route' => ['leagues.charters.request_approval', $league->slug, $charter->slug ], 'style' => 'display: inline-block;' ] ) !!}
        {!! Form::submit("Submit for approval", ['class' => 'btn btn-success'] ) !!}
      {!! Form::close() !!}
    @endif
    @if( Auth::user()->can('charter-edit') && ( ( Auth::user()->id == $league->user_id ) || Auth::user()->hasRole('root') ) )
      <a class="btn btn-default" href="{{ route('leagues.charters.edit', [ $league->slug, $charter->slug ] ) }}">Upload a new revision</a>
    @endif
    @if( Auth::user()->can('charter-delete') && ( ( Auth::user()->id == $league->user_id ) || Auth::user()->hasRole('root') ) )
      {!! Form::model( $charter, ['method' => 'delete', 'route' => ['leagues.charters.delete', $league->slug, $charter->slug ], 'style' => 'display: inline-block;' ] ) !!}
        {!! Form::submit("Delete this draft", ['class' => 'btn btn-danger'] ) !!}
      {!! Form::close() !!}
    @endif
    @if( $charter->isDeleted() && ( Auth::user()->can('charter-create') || ( Auth::user()->hasRole('root') ) ) )
      {!! Form::model( $charter, ['method' => 'patch', 'route' => ['leagues.charters.restore', $league->slug, $charter->slug ], 'style' => 'display: inline-block;' ] ) !!}
        {!! Form::submit("Restore this draft", ['class' => 'btn btn-success'] ) !!}
      {!! Form::close() !!}
    @endif
  @endif

  @if( $charter->approval_requested_at && !$charter->active_from )

    <p>Charter was submitted for approval <span class="time" title="{{ $charter->approval_requested_at->toDateString() }}">{{ $charter->approval_requested_at->diffForHumans() }}</span>.</p>

    @if( Auth::user()->can('charter-approve') || Auth::user()->can('charter-reject') )
      <ul class="nav nav-tabs" role="tablist">
        @if( Auth::user()->can('charter-approve') )
          <li><a href="#approve" role="tab" data-toggle="tab">Approve this charter</a></li>
        @endif

        @if( Auth::user()->can('charter-reject') )
          <li><a href="#reject" role="tab" data-toggle="tab">Reject this charter</a></li>
        @endif
      </ul>

      <div class="tab-content">
        @if( Auth::user()->can('charter-approve') )
          <div class="tab-pane" id="approve" style="padding: 20px 0 0;">
            {!! Form::model( $charter, ['method' => 'PATCH', 'route' => ['leagues.charters.approve', $league->slug, $charter->slug ] ] ) !!}
              <div class="form-group">
                {!! Form::label('active_from', 'Active From:') !!}
                <?php
                if( $charter->effective_from )
                {
                  $active_from = $charter->effective_from;
                }
                else
                {
                  $active_from = \Carbon\Carbon::now()->addDays( $league->approvedCharters( $charter->charter_type_id )->count() ? 30 : 0 );
                }
                ?>
                {!! Form::date('active_from', $active_from, ['class' => 'form-control'] ) !!}
              </div>
              <div class="form-group">
                {!! Form::submit('Approve', ['class' => 'btn btn-success'] ) !!}
              </div>
            {!! Form::close() !!}
          </div>
        @endif

        @if( Auth::user()->can('charter-reject') )
          <div class="tab-pane" id="reject" style="padding: 20px 0 0;">
            {!! Form::model( $charter, ['method' => 'PATCH', 'route' => ['leagues.charters.reject', $league->slug, $charter->slug ] ] ) !!}
              @if( $charter->rejection_reason )
                <p>This charter was rejected previously, the most recent reason being: {{ $charter->rejection_reason }}</p>
              @endif
              <div class="form-group">
                {!! Form::label('rejection_reason', 'Give a reason for rejection:') !!}
                {!! Form::text('rejection_reason', null, ['class' => 'form-control'] ) !!}
              </div>
              <div class="form-group">
                {!! Form::submit('Reject', ['class' => 'btn btn-danger'] ) !!}
              </div>
            {!! Form::close() !!}
          </div>
        @endif
      </div>
    @endif
  @endif

  @if( $charter->active_from )
    <p>Charter {{ $charter->active_from < \Carbon\Carbon::now() ? 'became' : 'will become' }} active <span class="time" title="{{ $charter->active_from->toDateString() }}">{{ $charter->active_from->diffForHumans() }}</span></p>
  @endif

  @if( !$charter->skaters->count() )
    <p>{{ $charter->name }} contains no skaters.</p>
  @else
    <table class="table table-striped">
      <thead>
        <tr>
          <th></th>
          <th>Skate Name</th>
          <th>Legal Name</th>
          <th>Number</th>
        </tr>
      </thead>
      <tbody>
        @foreach( $charter->skaters as $key => $skater )
          <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $skater->name }}</td>
            <td>{{ $skater->legal_name }}</td>
            <td>{{ $skater->number }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

@endsection
