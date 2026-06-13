# Live Tracking Simplified - Database-Based Approach

**Date:** 2026-06-13
**Change:** Removed Redis streaming complexity, using database batches instead

---

## What Changed

### Before (Complex Redis Streaming):
- App tried to stream GPS to Redis endpoint every 1-30 seconds
- Dashboard fetched from Redis cache
- **Problem:** Streaming from app not working reliably
- **Result:** Live map showing no riders despite active duty sessions

### After (Simple Database Batches):
- App sends batch uploads every 2 minutes (already working perfectly)
- Dashboard fetches from `location_batches` table
- **Solution:** Use what already works - the batch upload system
- **Result:** Reliable live tracking based on recent database uploads

---

## Technical Changes

### File Modified: `app/Http/Controllers/Web/RealTimeMapController.php`

#### 1. Removed Redis Dependency
```php
// BEFORE:
use App\Services\Cache\RealTimeLocationService;
$locations = $this->realTimeLocation->getAllOnlineRiders();

// AFTER:
use Illuminate\Support\Facades\DB;
$latestBatches = DB::table('location_batches')...
```

#### 2. New Query Logic
```sql
-- Get latest batch for each rider in last 30 minutes
SELECT lb1.*
FROM location_batches lb1
JOIN (
    SELECT rider_id, MAX(batch_end_time) as max_time
    FROM location_batches
    WHERE batch_end_time >= NOW() - INTERVAL 30 MINUTE
    GROUP BY rider_id
) lb2 ON lb1.rider_id = lb2.rider_id
     AND lb1.batch_end_time = lb2.max_time
```

#### 3. Extract Latest GPS Point
```php
// Each batch contains multiple GPS points in JSON
$points = json_decode($batch->points, true);
$latestPoint = end($points); // Get most recent point

// Reconstruct timestamp
$batchDate = substr($batch->batch_end_time, 0, 10);
$recordedAt = Carbon::parse($batchDate . ' ' . $latestPoint['ts']);
```

#### 4. Response Format (Same as Before)
```json
[
  {
    "id": 2,
    "name": "Sohel",
    "username": "sohel",
    "latitude": 3.025591,
    "longitude": 101.75316,
    "accuracy": 5.1,
    "speed": 8.0,
    "recorded_at": "12 minutes ago",
    "recorded_at_full": "2026-06-13 12:49:16",
    "is_online": true,
    "is_on_duty": true,
    "duty_started_at": "01:00 PM",
    "duty_duration": "1 hour",
    "status": "Online"
  }
]
```

---

## Configuration

### Time Window: 30 Minutes
```php
// Show riders who uploaded GPS in last 30 minutes
$thirtyMinutesAgo = Carbon::now()->subMinutes(30);

// Mark as "Online" if within 30 minutes
$isOnline = $recordedAt->diffInMinutes(now()) < 30;
```

**Why 30 minutes?**
- Batch uploads happen every 2 minutes
- 30-minute window handles timezone discrepancies
- Accounts for network delays
- Still shows "real-time" for practical purposes

---

## How It Works Now

### 1. App Side (No Changes Needed)
```
Every 2 minutes:
1. App collects GPS points
2. Sends batch to /api/locations/bulk
3. Backend saves to location_batches table
```

### 2. Dashboard Side (Simplified)
```
Auto-refresh (1 minute interval, OFF by default):
1. Fetch /realtime/riders
2. Query location_batches table
3. Get latest batch per rider
4. Extract latest GPS point
5. Show on map
```

### 3. Database Structure
```sql
location_batches:
- rider_id
- duty_session_id
- batch_start_time
- batch_end_time
- point_count
- points (JSON array):
  [
    {"lat": 3.025591, "lng": 101.75316, "acc": 5.1, "spd": 8.0, "ts": "12:49:16"},
    ...
  ]
```

---

## Benefits

### ✅ Reliability
- Uses existing batch upload system (already working)
- No dependency on Redis streaming
- Database is single source of truth

### ✅ Simplicity
- Less code complexity
- Fewer moving parts
- Easier to debug

### ✅ Performance
- Database queries are fast (indexed on rider_id, batch_end_time)
- 30-minute window limits query scope
- JSON extraction is efficient

### ✅ Accuracy
- Same GPS data as batch uploads
- Phase 3 accuracy improvements (Kalman filter, road snapping)
- Typically 4-5 meters accuracy

---

## Verification

### Check if Sohel is showing:

```bash
# 1. Check active duty session
php8.4 artisan tinker --execute="
\$session = App\Models\DutySession::where('rider_id', 2)
    ->where('status', 'active')
    ->first();
echo \$session ? 'Active' : 'Inactive';
"

# 2. Check recent batch uploads
php8.4 artisan tinker --execute="
\$count = DB::table('location_batches')
    ->where('rider_id', 2)
    ->where('batch_end_time', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 30 MINUTE)'))
    ->count();
echo 'Recent batches: ' . \$count;
"

# 3. Test API endpoint
curl https://tracking-rider.digibox.com.bd/realtime/riders
```

---

## Current Status

### ✅ Working:
- Batch uploads every 2 minutes
- Database storage
- Live map endpoint
- GPS accuracy (4-5m)

### ✅ Verified:
- Sohel (rider 2) has active duty session #56
- Recent batch uploaded at 12:50:00
- Latest GPS: (3.025591, 101.75316) with 5.1m accuracy

### 📍 Dashboard:
- Open https://tracking-rider.digibox.com.bd/tracking
- Click "Live Tracking" tab
- Should show Sohel on map
- Auto-refresh: OFF by default (toggle to enable)

---

## Troubleshooting

### If rider not showing:

1. **Check duty status:**
   - Is rider on duty? (active duty session)

2. **Check recent uploads:**
   - Any batch uploads in last 30 minutes?

3. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "Live Map"
   ```

4. **Check browser console:**
   - F12 → Console tab
   - Should show: "📍 Loaded X online riders"

---

**Last Updated:** 2026-06-13 11:01
**Status:** Simplified and deployed - using database batches for live tracking
