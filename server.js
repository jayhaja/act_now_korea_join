const express = require('express');
const bodyParser = require('body-parser');
const mongoose = require('mongoose');
const cors = require('cors');

const app = express();

// CORS 미들웨어 추가: 모든 도메인을 허용 (모든 OPTIONS 요청 처리)
app.use(cors({ origin: '*' }));
app.options('*', cors());

// JSON 요청 파싱 미들웨어
app.use(bodyParser.json());

// MongoDB 연결 (환경변수가 있으면 사용하고, 없으면 로컬 연결)
mongoose.connect(process.env.MONGODB_URI || 'mongodb://localhost:27017/kakaoUsers', {
  useNewUrlParser: true,
  useUnifiedTopology: true
})
.then(() => {
  console.log("MongoDB 연결 성공");
})
.catch(err => {
  console.error("MongoDB 연결 오류:", err);
});

// 사용자 스키마 정의 (카카오 로그인으로 받은 정보를 저장)
const userSchema = new mongoose.Schema({
  id: { type: String, unique: true },
  profile_nickname: String,
  account_email: String,
  gender: String,
  age_range: String,
  birthyear: String,
  phone_number: String,
  createdAt: { type: Date, default: Date.now }
});
const User = mongoose.model('User', userSchema);

// API 엔드포인트: 프론트엔드에서 전달받은 사용자 정보를 MongoDB에 저장
app.post('/api/registerKakaoUser', async (req, res) => {
  try {
    const kakaoUser = req.body;
    console.log("프론트엔드에서 받은 사용자 정보:", kakaoUser);

    let user = await User.findOne({ id: kakaoUser.id });
    if (!user) {
      user = new User({
        id: kakaoUser.id,
        profile_nickname: kakaoUser.properties ? kakaoUser.properties.nickname : '',
        account_email: kakaoUser.kakao_account ? kakaoUser.kakao_account.email : '',
        gender: kakaoUser.kakao_account ? kakaoUser.kakao_account.gender : '',
        age_range: kakaoUser.kakao_account ? kakaoUser.kakao_account.age_range : '',
        birthyear: kakaoUser.kakao_account ? kakaoUser.kakao_account.birthyear : '',
        phone_number: kakaoUser.kakao_account ? kakaoUser.kakao_account.phone_number : ''
      });
      await user.save();
      console.log("신규 사용자 저장 완료");
    } else {
      console.log("기존 사용자 발견");
    }
    res.json({ success: true, message: "회원가입이 완료되었습니다." });
  } catch (error) {
    console.error("데이터베이스 저장 오류:", error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// 서버 시작 (환경변수 PORT 사용, 없으면 3000번 포트)
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
