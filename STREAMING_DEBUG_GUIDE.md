# Real-Time GPS Streaming Debug Guide
**Date:** 2026-06-13
**Issue:** App not calling /api/locations/stream endpoint

---

## Current Status

### ✅ WORKING:
- Live map display (frontend fixed)
- RealTimeLocationService (backend)
- RealTimeMapController (endpoints)
- Phase 3 GPS accuracy (4.67m!)
- Batch uploads (every 2 minutes)
- Duty session tracking

### ❌ NOT WORKING:
- Real-time streaming from app to Redis
- Live marker movement on map

---

## Evidence

### Logs Show Only Batch Uploads:
```
[11:58:27] 📥 [Bulk Upload] Received request
[11:58:27] ✅ [Bulk Upload] Successfully inserted

NO 🔴 [STREAM] logs found
```

### Redis Shows Test Data Only:
```bash
redis-cli GET digibox-rider-tracker-database-realtime:rider:location:1
# Returns manually added test data, not app-streamed data
```

---

## Why Streaming Might Not Work

### 1. Background Service Not Restarted
**Issue:** Old background service still running with old code (no streaming)

**Solution:**
```
Force Stop app → Reboot phone → Start duty fresh
```

### 2. Network/HTTP Error
**Issue:** Stream calls failing silently (code exists but fails)

**Debug:**
```bash
# Monitor Laravel logs for stream errors
ssh digibox "cd /var/www/html/digibox.com.bd/tracking-rider.digibox.com.bd && tail -f storage/logs/laravel.log | grep -E 'STREAM|stream|401|403|500'"
```

### 3. Authentication Issue
**Issue:** API token not being sent correctly

**Check:**
```dart
// In api_service.dart:
Future<void> streamLocation(LocationPoint location) async {
  final response = await _httpClient.post(
    Uri.parse(ApiConfig.locationStream),
    headers: _getHeaders(),  // ← Must include auth token
    body: jsonEncode(location.toJson()),
  );
}
```

### 4. Accuracy Threshold Not Met
**Issue:** Code only streams if accuracy < 30m

**Check logs for:**
```
GPS accuracy: 42.4m  // ← Would NOT stream (>30m)
GPS accuracy: 4.67m  // ← Would stream (<30m)
```

---

## How Streaming Should Work

### Flow:
```
1. App acquires GPS (every 1-30 seconds)
2. Process through Phase 3 pipeline:
   - Kalman Filter → Trajectory Predictor → Road Snapper
3. Check accuracy < 30m
4. Call POST /api/locations/stream (fire and forget)
5. Backend stores in Redis with 5-minute TTL
6. Dashboard fetches from Redis every 60 seconds
7. Marker updates on map
```

### Expected Logs (When Working):
```
📱 APP SIDE:
🎯✅ GPS: (3.025572, 101.75315) | Acc: 4.7m | Speed: 0.6 km/h | Conf: 85%
[Stream] Sending to /api/locations/stream

🖥️ SERVER SIDE:
🔴 [STREAM] Received real-time GPS stream {"rider_id":1,"latitude":3.025572,...}
✅ [Stream] Stored in Redis with TTL 300s
```

---

## Monitoring Commands

### 1. Watch for Stream Calls (Real-Time)
```bash
ssh digibox "cd /var/www/html/digibox.com.bd/tracking-rider.digibox.com.bd && timeout 60 tail -f storage/logs/laravel.log | grep --line-buffered STREAM"
```

### 2. Check Redis for New Data
```bash
ssh digibox "watch -n 2 'redis-cli GET digibox-rider-tracker-database-realtime:rider:location:1 | jq .'"
```

### 3. Check Last GPS Upload Time
```bash
ssh digibox "cd /var/www/html/digibox.com.bd/tracking-rider.digibox.com.bd && php8.4 artisan tinker --execute=\"
\\\$latest = DB::table('location_batches')->where('rider_id', 1)->orderBy('created_at', 'desc')->first();
echo 'Last GPS: ' . \\\$latest->created_at . ' | Accuracy: ' . \\\$latest->avg_accuracy . 'm' . PHP_EOL;
\""
```

---

## Testing Steps

### Phase 1: Verify App is Running New Code
**Expected:** GPS logs show Phase 3 indicators (🎯✅⚠️)

### Phase 2: Check Network Connectivity
**Test:**
```bash
# From phone, test stream endpoint manually
curl -X POST https://tracking-rider.digibox.com.bd/api/locations/stream \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

### Phase 3: Monitor Logs During Active Duty
**Run on server while rider is on duty:**
```bash
tail -f storage/logs/laravel.log | grep -E "STREAM|Bulk"
```

**Expected if working:**
```
🔴 [STREAM] Received real-time GPS stream  ← Every 1-30 seconds
📥 [Bulk Upload] Received request           ← Every 2 minutes
```

**Current (broken):**
```
📥 [Bulk Upload] Received request           ← Every 2 minutes only
(NO STREAM logs)
```

---

## Fix Checklist

- [ ] Force stop app
- [ ] Clear app cache
- [ ] Reboot phone
- [ ] Stop duty (if active)
- [ ] Start duty fresh
- [ ] Wait for GPS lock (10 seconds)
- [ ] Walk around (trigger GPS updates)
- [ ] Monitor server logs for 🔴 [STREAM]
- [ ] Check Redis for new data
- [ ] Refresh live map

---

## Success Criteria

### When Streaming Works:
1. ✅ Server logs show `🔴 [STREAM]` every 1-30 seconds
2. ✅ Redis data updates automatically
3. ✅ Map marker moves as rider moves
4. ✅ "Last update" timestamp changes frequently
5. ✅ Trail shows on map

### Current State (Before Fix):
1. ❌ No stream logs
2. ❌ Redis has only manual test data
3. ❌ Map shows static marker
4. ❌ No trail

---

## Next Steps

1. **User:** Force stop app, reboot phone, start duty fresh
2. **Monitor:** Watch server logs for stream calls
3. **Debug:** If no streams, check app logs for errors
4. **Fix:** Address specific error found

---

**Last Updated:** 2026-06-13 12:20
**Status:** Waiting for user to restart app and test streaming
