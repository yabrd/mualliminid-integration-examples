import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";
import { fileURLToPath, URL } from "node:url";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  return {
    plugins: [vue(), tailwindcss()],
    resolve: {
      alias: {
        "@": fileURLToPath(new URL("./src", import.meta.url)),
      },
    },
    server: {
      port: env.VITE_PORT ? parseInt(env.VITE_PORT, 10) : 5011,
      proxy: {
        "/api": {
          target: "http://localhost:3011",
          changeOrigin: true,
        },
      },
    },
  };
});
