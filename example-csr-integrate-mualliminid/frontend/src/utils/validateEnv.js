const REQUIRED_VARS = [
  "VITE_API_BASE_URL",
  "VITE_API_SSO_URL",
  "VITE_SSO_CLIENT_ID"
];

const missing = REQUIRED_VARS.filter((key) => !import.meta.env[key]);
if (missing.length) {
  throw new Error(`❌ Missing environment variables:\n${missing.map((k) => `   - ${k}`).join("\n")}`);
}
