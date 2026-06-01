```markdown
# FCM Token API Integration Guide for Flutter

This document explains how to register and remove Firebase Cloud Messaging (FCM) tokens for push notifications.

## Base URL

```
https://your-domain.com/api/v1
```

## Authentication

All endpoints require a **Bearer token** obtained after login.  
Include the token in the `Authorization` header:

```
Authorization: Bearer {your_access_token}
```

## Endpoints

| Method | Endpoint            | Description               |
|--------|---------------------|---------------------------|
| POST   | `/fcm-token`        | Store/update FCM token    |
| DELETE | `/fcm-token`        | Remove FCM token          |

---

## 1. Store FCM Token

Used after the user logs in or when the FCM token is refreshed.

### Request

- **URL:** `/fcm-token`
- **Method:** `POST`
- **Headers:**  
  `Content-Type: application/json`  
  `Authorization: Bearer <token>`

- **Body:**

```json
{
  "fcm_token": "string (max 500 chars)"
}
```

### Response (200 OK)

```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "FCM token stored successfully.",
  "data": null,
  "timestamp": "2026-06-01T12:00:00.000Z",
  "status_code": 200
}
```

### Flutter Example (using http package)

```dart
Future<void> storeFcmToken(String token) async {
  final response = await http.post(
    Uri.parse('$baseUrl/fcm-token'),
    headers: {
      'Authorization': 'Bearer $accessToken',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'fcm_token': token}),
  );

  if (response.statusCode == 200) {
    print('Token stored successfully');
  } else {
    // handle error
  }
}
```

---

## 2. Remove FCM Token

Call this when the user logs out or when the app no longer wants to receive notifications.

### Request

- **URL:** `/fcm-token`
- **Method:** `DELETE`
- **Headers:**  
  `Authorization: Bearer <token>`

- **Body:** (empty)

### Response (200 OK)

```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "FCM token removed.",
  "data": null,
  "timestamp": "2026-06-01T12:00:00.000Z",
  "status_code": 200
}
```

### Flutter Example

```dart
Future<void> removeFcmToken() async {
  final response = await http.delete(
    Uri.parse('$baseUrl/fcm-token'),
    headers: {
      'Authorization': 'Bearer $accessToken',
    },
  );

  if (response.statusCode == 200) {
    print('Token removed');
  }
}
```

---

## Error Responses

All errors follow a consistent format:

```json
{
  "success": false,
  "code": "ERROR_CODE",
  "message": "Human-readable message",
  "timestamp": "...",
  "status_code": 4xx/5xx
}
```

Common error codes:

| HTTP Status | `code`                    | Meaning                         |
|-------------|---------------------------|---------------------------------|
| 401         | `UNAUTHORIZED`            | Invalid or missing token        |
| 422         | `VALIDATION_FAILED`       | `fcm_token` missing or too long |
| 429         | `TOO_MANY_REQUESTS`       | Rate limit exceeded             |
| 500         | `INTERNAL_SERVER_ERROR`   | Server error                    |

### Validation error example (422)

```json
{
  "success": false,
  "code": "VALIDATION_FAILED",
  "message": "Validation failed",
  "errors": {
    "fcm_token": ["The fcm token field is required."]
  },
  "timestamp": "...",
  "status_code": 422
}
```

---

## Integration Workflow

### 1. After login / app start

- Get the FCM token using `firebase_messaging` plugin.
- Call `POST /fcm-token` to associate it with the user.

### 2. On token refresh

Firebase may refresh the token. Listen to `onTokenRefresh` and call the store endpoint again.

### 3. On logout

- Call `DELETE /fcm-token` to remove the token from the server.
- Then proceed with the normal logout flow.

### 4. On app uninstall / clear data

No action needed – the server will keep stale tokens but they will be harmless.  
Optionally you can implement a cleanup job on the backend.

---

## Notes for Flutter Developers

- Always send a **valid Bearer token** (obtained from login/register endpoints).
- The `fcm_token` must be a **non‑empty string**, up to 500 characters.
- The API does **not** require `allow_notifications` to be true; the token is stored regardless.
- You can retrieve the current user's FCM token status via the `GET /me` endpoint (includes `fcm_token` and `allow_notifications` fields if needed).
- If the user is frozen or deactivated, the token can still be stored but notifications may be suppressed by the backend.
