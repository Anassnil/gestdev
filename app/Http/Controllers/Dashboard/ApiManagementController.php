<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Api;
use App\Models\ApiCollection;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiManagementController extends Controller
{
    /* ── Index ──────────────────────────────────────────────── */
    public function index(Request $request)
    {
        $user = $request->user();
        $apis = Api::where('user_id', $user->id)
            ->withCount(['endpoints', 'collections', 'environments', 'versions'])
            ->orderByDesc('updated_at')
            ->get();

        $stats = [
            'total'       => $apis->count(),
            'active'      => $apis->where('status', 'active')->count(),
            'draft'       => $apis->where('status', 'draft')->count(),
            'deprecated'  => $apis->where('status', 'deprecated')->count(),
            'endpoints'   => $apis->sum('endpoints_count'),
            'collections' => $apis->sum('collections_count'),
        ];

        return view('dashboard.api_management.index', compact('apis', 'stats'));
    }

    /* ── Show ───────────────────────────────────────────────── */
    public function show(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);

        $api->load([
            'collections',
            'environments',
            'versions' => fn ($q) => $q->orderByDesc('created_at'),
            'endpoints' => fn ($q) => $q->with('collection')->orderBy('method')->orderBy('path'),
        ]);

        return view('dashboard.api_management.show', compact('api'));
    }

    /* ── Store API ──────────────────────────────────────────── */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'base_url'    => 'nullable|url|max:500',
            'version'     => 'nullable|string|max:50',
            'status'      => 'required|in:draft,active,deprecated',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['version'] = $data['version'] ?: '1.0.0';
        $base = Str::slug($data['name']) ?: 'api';
        $slug = $base;
        $i = 1;
        while (Api::where('user_id', $data['user_id'])->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $data['slug'] = $slug;

        $api = Api::create($data);
        return redirect()->route('dashboard.api_management.show', $api)
            ->with('success', 'API "' . $api->name . '" created.');
    }

    /* ── Update API ─────────────────────────────────────────── */
    public function update(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'base_url'    => 'nullable|url|max:500',
            'version'     => 'nullable|string|max:50',
            'status'      => 'required|in:draft,active,deprecated',
        ]);

        $api->update($data);
        return back()->with('success', 'API updated.');
    }

    /* ── Destroy API ────────────────────────────────────────── */
    public function destroy(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $api->delete();
        return redirect()->route('dashboard.api_management.index')
            ->with('success', 'API deleted.');
    }

    /* ── Endpoints ──────────────────────────────────────────── */
    public function storeEndpoint(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'path'          => 'required|string|max:500',
            'method'        => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'description'   => 'nullable|string|max:2000',
            'collection_id' => 'nullable|exists:api_collections,id',
            'status'        => 'required|in:draft,active,deprecated',
        ]);
        $data['api_id'] = $api->id;
        ApiEndpoint::create($data);
        return back()->with('success', 'Endpoint added.');
    }

    public function updateEndpoint(Request $request, Api $api, ApiEndpoint $endpoint)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'path'          => 'required|string|max:500',
            'method'        => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'description'   => 'nullable|string|max:2000',
            'collection_id' => 'nullable|exists:api_collections,id',
            'status'        => 'required|in:draft,active,deprecated',
        ]);
        $endpoint->update($data);
        return back()->with('success', 'Endpoint updated.');
    }

    public function destroyEndpoint(Request $request, Api $api, ApiEndpoint $endpoint)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $endpoint->delete();
        return back()->with('success', 'Endpoint deleted.');
    }

    /* ── Collections ────────────────────────────────────────── */
    public function storeCollection(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        ApiCollection::create(array_merge($data, ['api_id' => $api->id]));
        return back()->with('success', 'Collection created.');
    }

    public function destroyCollection(Request $request, Api $api, ApiCollection $collection)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $collection->delete();
        return back()->with('success', 'Collection deleted.');
    }

    /* ── Environments ───────────────────────────────────────── */
    public function storeEnvironment(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'base_url' => 'nullable|url|max:500',
        ]);
        ApiEnvironment::create(array_merge($data, ['api_id' => $api->id]));
        return back()->with('success', 'Environment added.');
    }

    public function destroyEnvironment(Request $request, Api $api, ApiEnvironment $environment)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $environment->delete();
        return back()->with('success', 'Environment deleted.');
    }

    /* ── Versions ───────────────────────────────────────────── */
    public function storeVersion(Request $request, Api $api)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'version'      => 'required|string|max:50',
            'release_date' => 'nullable|date',
            'status'       => 'required|in:draft,active,deprecated',
        ]);
        ApiVersion::create(array_merge($data, ['api_id' => $api->id]));
        return back()->with('success', 'Version added.');
    }

    public function destroyVersion(Request $request, Api $api, ApiVersion $version)
    {
        abort_if($api->user_id !== $request->user()->id, 403);
        $version->delete();
        return back()->with('success', 'Version deleted.');
    }
}
