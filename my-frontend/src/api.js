import axios from 'axios'

// Laravel API 用の axios（URL や共通ヘッダーをここにまとめておくと、各コンポーネントでは '/posts' だけ書けばよい）
// Docker の nginx は 80番ポートで動いているので http://localhost/api
const api = axios.create({
  baseURL: 'http://localhost/api',
  headers: {
    // JSON で返してもらう（バリデーションエラーも 422 + JSON で返る）
    Accept: 'application/json',
  },
})

export default api
