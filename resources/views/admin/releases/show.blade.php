@extends('layouts.admin')

@section('content')
  <div class="mb-3">
    <a href="{{ route('admin.releases.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Back to releases</a>
  </div>

  <div class="row">
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Release {{ $release->version }}</h5>
          <p class="mb-1"><strong>Branch:</strong> {{ $release->branch }}</p>
          <p class="mb-1"><strong>Generated:</strong> {{ $release->generated_at->toDayDateTimeString() }}</p>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h6>Changelog Summary</h6>
          @if($release->changelogs->isEmpty())
            <p class="text-muted mb-0">No changelog entries.</p>
          @else
            @foreach($release->changelogs->groupBy('category') as $category => $items)
              <div class="mb-2">
                <strong>{{ $category }}</strong>
                <ul class="mb-0">
                  @foreach($items as $item)
                    <li>{{ $item->description }}</li>
                  @endforeach
                </ul>
              </div>
            @endforeach
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Full Changelog</h5>
          @if($release->changelogs->isEmpty())
            <p class="text-muted">No changelog entries.</p>
          @else
            @foreach($release->changelogs->groupBy('category') as $category => $items)
              <h6 class="mt-3">{{ $category }}</h6>
              <ul>
                @foreach($items as $item)
                  <li>
                    <div>{{ $item->description }}</div>
                    @if($item->details)
                      <div class="text-muted small">{!! nl2br(e($item->details)) !!}</div>
                    @endif
                  </li>
                @endforeach
              </ul>
            @endforeach
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Commits</h5>
          @if($release->commits->isEmpty())
            <p class="text-muted">No commits recorded.</p>
          @else
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Hash</th>
                    <th>Author</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($release->commits as $commit)
                    <tr>
                      <td><code>{{ Str::limit($commit->commit_hash, 10) }}</code></td>
                      <td>{{ $commit->author }}</td>
                      <td>{{ $commit->type }}</td>
                      <td>{{ $commit->message }}</td>
                      <td>{{ optional($commit->authored_at)->diffForHumans() }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

@endsection
