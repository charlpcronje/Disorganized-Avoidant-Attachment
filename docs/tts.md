# 🎙️ TTS Microservice — Design & Documentation

## Overview
This is a secure, cache-aware Text-to-Speech (TTS) microservice built using Python (Flask) and OpenAI’s TTS API. It allows clients to convert written text into spoken audio via a `/speak` endpoint while preventing abuse and ensuring cache integrity.

## Key Features
- Uses OpenAI’s latest TTS model (`tts-1`)
- Dual-generation validation to prevent partial/corrupt outputs
- SHA256-based cache identity including voice and text
- Configurable tolerance for validating TTS stability
- Restricts usage to authorized domains (e.g., `*.webally.co.za`)
- Cached audio returned instantly if previously generated

---

## API Endpoints

### `POST /speak`
**Generate or retrieve TTS audio for a given text.**

#### Request Body
```json
{
  "text": "You were never broken",
  "voice": "onyx",
  "domain": "info.nade.webally.co.za"
}
```

#### Required Fields
- `text` (string): Text to be converted into speech
- `voice` (string): One of OpenAI’s supported voices
- `domain` (string): Origin domain of the request (used for validation)

#### Response (on success)
```json
{
  "audio_url": "/audio/onyx_a1b2c3d4e5f6.mp3"
}
```

#### Response (on validation failure)
```json
{
  "error": "Validation failed: size mismatch (11.23%)"
}
```

---

## How It Works
1. Hashes the combination of `voice + text` to create a unique filename.
2. Checks for a matching file in the cache directory.
3. If not cached, generates two TTS files via OpenAI.
4. Compares their sizes. If within `SIZE_DIFF_TOLERANCE` (default 10%), saves one as final.
5. Returns the relative URL to the audio file.

---

## .env Configuration
```dotenv
OPENAI_API_KEY=your-openai-api-key
PORT=8444
CACHE_DIR=cache
ALLOWED_DOMAIN_SUFFIX=.webally.co.za
TTS_MODEL=tts-1
SIZE_DIFF_TOLERANCE=0.10
```

---

## Supported Voices (Verified as of April 2025)

The following voices are officially supported by OpenAI's `tts-1` model according to the latest published documentation and the interactive demo at [OpenAI.fm](https://openai.fm). These voices are optimized for English and the list may continue to evolve. Always check the source to ensure you're referencing the most current capabilities.

| Voice   | Description                                                                 |
|---------|-----------------------------------------------------------------------------|
| `alloy`   | Balanced and smooth. Neutral tone. Great for general-purpose TTS           |
| `ash`     | Crisp and slightly assertive. Good for formal or confident delivery        |
| `ballad`  | Slow, poetic tone. Designed for gentle narration or lyrical content        |
| `coral`   | Rich and textured. Works well for expressive storytelling                  |
| `echo`    | Soft-spoken and ethereal. Ideal for reflective tones or meditative text    |
| `fable`   | Warm and narrative-driven. Best for longform audio and immersive stories   |
| `onyx`    | Deep, clear male voice. Calm and grounded. Great for authoritative delivery|
| `nova`    | Bright and emotionally rich. Excellent for engaging conversational style   |
| `sage`    | Soothing and wise-sounding. Suitable for calm explanation or support       |
| `shimmer` | Playful, animated energy. Great for light content or interactive narration |

**Note:** The `cove` voice is currently only available inside ChatGPT apps and is not exposed via the public API as of April 2025.

---

## Example Use Cases

### 1. Personal Companion UI
```html
<talk voice="fable">The world doesn't end in fire, but in forgiveness.</talk>
```
JavaScript reads `<talk>` blocks and fetches from `/speak` when button is clicked.

### 2. Cached Podcast Transcriptions
Generate segments as needed, serve from `/audio/` when requested by player.

### 3. Mental Health Journals
Add spoken word versions to entries with reassuring voices like `nova` or `echo`.

---

## Deployment Tips
- Use `gunicorn` or `uvicorn` in production:
  ```bash
  gunicorn -w 4 -b 0.0.0.0:8444 app:app
  ```
- Protect public access with rate limiting (e.g. `fail2ban`, Cloudflare rules)
- Mount `CACHE_DIR` on persistent storage

---

## Notes
- `stream_to_file()` requires a valid path, not a file handle.
- `SIZE_DIFF_TOLERANCE` helps mitigate OpenAI’s non-deterministic outputs.
- Can easily be extended to support SSML or real-time streaming with WebSocket upgrades.

---

## Maintainer
**Charl Cronje**  
Project: Amicus TTS Layer  
Website: [iamicus.me](https://iamicus.me)

