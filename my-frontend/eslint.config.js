import js from '@eslint/js'
import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'

export default [
  { ignores: ['dist'] },
  js.configs.recommended,
  // .vue ファイルを読めるようにし、Vue 向けの基本ルールを有効にする
  ...pluginVue.configs['flat/essential'],
  {
    files: ['**/*.{js,vue}'],
    languageOptions: {
      globals: globals.browser,   // console や document などを使えるようにする
    },
    rules: {
      // 未使用変数をエラーに
      'no-unused-vars': 'error',
      // console.logを警告に（本番コードには残さない）
      'no-console': 'warn',
      // == ではなく === を強制
      'eqeqeq': 'error',
    }
  }
]