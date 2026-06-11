<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InstallationLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstallationController extends Controller
{
    public function index()
    {
        $installations = InstallationLocation::orderBy('created_at', 'desc')->paginate(15);
        return view('installations.index', compact('installations'));
    }

    public function create()
    {
        return view('installations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_meters' => 'required|integer|min:10|max:500',
        ]);

        // Handle checkbox separately (checkbox sends nothing when unchecked)
        $validated['is_active'] = $request->has('is_active') ? true : false;

        InstallationLocation::create($validated);

        return redirect()->route('installations.index')->with('success', 'Installation location created successfully!');
    }

    public function edit(InstallationLocation $installation)
    {
        return view('installations.edit', compact('installation'));
    }

    public function update(Request $request, InstallationLocation $installation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_meters' => 'required|integer|min:10|max:500',
        ]);

        // Handle checkbox separately (checkbox sends nothing when unchecked)
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $installation->update($validated);

        return redirect()->route('installations.index')->with('success', 'Installation location updated successfully!');
    }

    public function destroy(InstallationLocation $installation)
    {
        $installation->delete();
        return redirect()->route('installations.index')->with('success', 'Installation location deleted successfully!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($data);

        $imported = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            if (count($row) < 4) {
                $errors[] = "Row " . ($index + 2) . ": Insufficient columns";
                continue;
            }

            try {
                InstallationLocation::create([
                    'name' => $row[0],
                    'address' => $row[1] ?? null,
                    'latitude' => (float)$row[2],
                    'longitude' => (float)$row[3],
                    'geofence_radius_meters' => isset($row[4]) ? (int)$row[4] : 100,
                    'is_active' => true,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $message = "Imported $imported installation(s) successfully.";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " error(s) occurred.";
        }

        return redirect()->route('installations.index')->with('success', $message);
    }

    public function export()
    {
        $installations = InstallationLocation::all();

        $csv = "Name,Address,Latitude,Longitude,Geofence Radius (m),Active\n";
        foreach ($installations as $installation) {
            $csv .= implode(',', [
                '"' . str_replace('"', '""', $installation->name) . '"',
                '"' . str_replace('"', '""', $installation->address ?? '') . '"',
                $installation->latitude,
                $installation->longitude,
                $installation->geofence_radius_meters,
                $installation->is_active ? '1' : '0',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="installations_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
