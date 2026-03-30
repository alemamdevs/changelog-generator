@extends('layouts.admin')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Releases</h1>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Version</th>
              <th>Branch</th>
              <th>Generated At</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($releases as $release)
              <tr>
                <td>{{ $release->version }}</td>
                <td>{{ $release->branch }}</td>
                <td>{{ $release->generated_at->toDayDateTimeString() }}</td>
                <td class="text-end">
                  <a href="{{ route('admin.releases.show', $release) }}" class="btn btn-sm btn-primary">View</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4">No releases found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center">
        {{ $releases->links() }}
      </div>
    </div>
  </div>

@endsection
