<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user has already spun the wheel
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT has_spun FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<div style='text-align: center; color: #FF0000; margin: 60px;'>Error: Usuario no encontrado.</div>";
        exit;
    }

    if ($user['has_spun']) {
        header("Location: inicio.php");
        exit;
    }
} catch (PDOException $e) {
    echo "<div style='text-align: center; color: #FF0000; margin: 60px;'>Error de base de datos: No se pudo verificar el estado del usuario. Por favor, verifica la configuración de la base de datos.</div>";
    exit;
}

// Define prizes
$prizes = [
    "Libro de Bienestar",
    "Batido Nutricional Gratis",
    "Par de Zapatos Deportivos",
    "Kit de Snacks Saludables",
    "Suplemento de Proteína"
];

// Handle prize selection and code generation
$redemption_code = '';
$selected_prize = '';
$selected_prize_index = -1;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spin'])) {
    $selected_prize_index = array_rand($prizes);
    $selected_prize = $prizes[$selected_prize_index];
    $redemption_code = strtoupper(bin2hex(random_bytes(4))); // e.g., A1B2C3D4
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET has_spun = 1, redemption_code = ?, prize = ? WHERE id = ?");
        $stmt->execute([$redemption_code, $selected_prize, $user_id]);
        $_SESSION['prize'] = $selected_prize;
        $_SESSION['redemption_code'] = $redemption_code;
    } catch (PDOException $e) {
        echo "<div style='text-align: center; color: #FF0000; margin: 60px;'>Error de base de datos: No se pudo guardar el premio. Por favor, verifica la configuración de la base de datos.</div>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ruleta de Premios - CAT21</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #000000;
      min-height: 100vh;
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* Header */
    .header {
      background: #000000;
      padding: 30px 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      border-bottom: 1px solid #333333;
      z-index: 10;
    }
    .logo {
      position: absolute; left: 60px; width: 60px; height: 60px;
      border-radius: 8px; transition: all 0.3s ease; object-fit: cover; cursor: pointer;
    }
    .logo:hover { transform: rotate(5deg); box-shadow: 0 4px 12px rgba(255,255,255,0.2); }
    .title { font-size: 28px; font-weight: 300; color: #ffffff; letter-spacing: 4px; text-transform: uppercase; }

    /* Container */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 20px;
      position: relative;
    }

    /* === Layout de dos columnas === */
    .two-col {
      display: grid;
      grid-template-columns: minmax(340px, 560px) 1fr;
      gap: 40px;
      align-items: start;
    }

    .left-col {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .right-col {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* Sección títulos */
    .wheel-section { position: relative; padding: 10px 0; }
    .wheel-title {
      font-size: 48px; font-weight: 700;
      background: linear-gradient(45deg, #FFD700, #FF6B6B, #4ECDC4, #45B7D1, #96CEB4);
      background-size: 400% 400%;
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
      margin-bottom: 8px; text-shadow: 0 0 30px rgba(255,255,255,0.5);
      animation: gradientShift 3s ease-in-out infinite; letter-spacing: 3px; text-transform: uppercase;
    }
    @keyframes gradientShift { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
    .wheel-subtitle {
      font-size: 20px; color: #ffffff; margin-bottom: 16px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.3); font-weight: 300; letter-spacing: 2px;
    }

    /* Ruleta */
    .wheel-container { position: relative; display: inline-block; margin: 10px auto 0; }
    .wheel-glow {
      position: absolute; top: -20px; left: -20px; right: -20px; bottom: -20px;
      background: conic-gradient(from 0deg,#ff0000,#ff8000,#ffff00,#80ff00,#00ff00,#00ff80,#00ffff,#0080ff,#0000ff,#8000ff,#ff00ff,#ff0080,#ff0000);
      border-radius: 50%; animation: rotate 8s linear infinite; z-index: -1; filter: blur(15px);
    }
    @keyframes rotate { from{transform:rotate(0)} to{transform:rotate(360deg)} }

    canvas {
      display: block; border-radius: 50%;
      box-shadow: 0 0 60px rgba(255,255,255,0.4), 0 0 100px rgba(255,215,0,0.3), inset 0 0 60px rgba(255,255,255,0.1);
      transition: all 0.3s ease; position: relative; z-index: 2;
    }
    canvas:hover {
      transform: scale(1.05);
      box-shadow: 0 0 80px rgba(255,255,255,0.6), 0 0 120px rgba(255,215,0,0.4), inset 0 0 60px rgba(255,255,255,0.2);
    }
    .canvas-hidden {
      opacity: 0 !important; transform: scale(0.8) !important; filter: blur(20px) !important;
      height: 0 !important; margin: 0 !important; transition: all 0.8s ease-out !important;
    }

    .wheel-pointer { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); z-index: 10; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.4)); }
    .pointer-arrow {
      width: 0; height: 0; border-left: 25px solid transparent; border-right: 25px solid transparent; border-top: 50px solid #FF0000; position: relative; animation: pointerPulse 2s ease-in-out infinite;
    }
    .pointer-arrow::after { content:''; position:absolute; top:-45px; left:-20px; width:0; height:0; border-left:20px solid transparent; border-right:20px solid transparent; border-top:40px solid #FF4444; }
    @keyframes pointerPulse { 0%,100%{transform:scale(1) translateY(0)} 50%{transform:scale(1.1) translateY(-5px)} }

    .spin-button {
      background: linear-gradient(45deg,#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEAA7);
      background-size: 400% 400%; color:#ffffff; border:none;
      padding: 20px 50px; font-size: 20px; font-weight:700; cursor:pointer; border-radius:50px;
      text-transform: uppercase; letter-spacing:3px; margin: 24px 10px 0;
      position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      transition: all 0.3s ease; animation: buttonGradient 4s ease-in-out infinite; min-width: 200px;
    }
    @keyframes buttonGradient { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
    .spin-button::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent); transition:left 0.6s; }
    .spin-button:hover::before { left: 100%; }
    .spin-button:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 15px 40px rgba(0,0,0,0.4); }
    .spin-button:active { transform: translateY(-1px) scale(1.02); }
    .spin-button:disabled { opacity: .7; cursor: not-allowed; transform: none; }

    /* Panel info derecha */
    .panel {
      background: linear-gradient(135deg, rgba(255,255,255,0.10), rgba(255,255,255,0.18));
      backdrop-filter: blur(16px);
      border: 2px solid rgba(255,255,255,0.28);
      border-radius: 18px;
      padding: 20px 22px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.18);
    }
    .panel h2 { font-size: 24px; color: #fff; font-weight: 700; margin-bottom: 10px; letter-spacing: .5px; }
    .prize-list { list-style: none; margin-top: 6px; }
    .prize-list li {
      display: flex; align-items: center; gap: 10px;
      color: #fff; font-weight: 600; padding: 10px 12px; border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.18);
      background: rgba(0,0,0,0.18); margin-bottom: 8px;
    }
    .prize-dot {
      width: 10px; height: 10px; border-radius: 50%;
      background: linear-gradient(45deg, #FFD700, #FF6B6B);
      box-shadow: 0 0 10px rgba(255,215,0,0.7);
    }

    /* Resultado (ahora en la izquierda) */
    .result {
      padding: 24px;
      background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.2));
      backdrop-filter: blur(20px);
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      animation: resultAppear .8s ease-out;
    }
    .left-col .result {
      width: 100%;
      max-width: 550px;       /* igual al canvas */
      margin-top: 16px;
      margin-left: auto;
      margin-right: auto;
    }
    @keyframes resultAppear { from{opacity:0; transform: translateY(30px) scale(.9)} to{opacity:1; transform: translateY(0) scale(1)} }
    .result h3 { font-size: 28px; color:#FFD700; margin-bottom: 14px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); animation: prizeGlow 2s ease-in-out infinite; }
    @keyframes prizeGlow { 0%,100%{text-shadow:0 2px 10px rgba(255,215,0,.5)} 50%{text-shadow:0 2px 20px rgba(255,215,0,.8),0 0 30px rgba(255,215,0,.6)} }
    .result .prize-name { font-size: 24px; color:#fff; font-weight: 700; margin: 10px 0; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
    .result .code-section { background: rgba(0,0,0,0.3); padding: 16px; border-radius: 15px; margin: 16px 0; border: 1px solid rgba(255,255,255,0.2); }
    .result .redemption-code { font-size: 22px; color:#4ECDC4; font-weight: 800; letter-spacing: 4px; margin: 8px 0; text-shadow: 0 0 15px rgba(78,205,196,0.5); animation: codeFlicker 3s ease-in-out infinite; }
    @keyframes codeFlicker { 0%,100%{opacity:1} 50%{opacity:.8; text-shadow:0 0 25px rgba(78,205,196,.8)} }

    /* Partículas y footer */
    .floating-particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; }
    .particle { position: absolute; width: 6px; height: 6px; background:#FFD700; border-radius: 50%; animation: float 6s ease-in-out infinite; }
    @keyframes float { 0%,100%{transform: translateY(100vh) rotate(0deg); opacity:0} 10%,90%{opacity:1} 50%{transform: translateY(-20px) rotate(180deg)} }

    .footer-minimal { text-align: center; padding: 40px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 40px; backdrop-filter: blur(10px); }
    .footer-minimal p { font-size: 12px; font-weight: 300; color: rgba(255,255,255,0.7); letter-spacing: 1px; text-transform: uppercase; }

    /* Responsive */
    @media (max-width: 992px) {
      .two-col { grid-template-columns: 1fr; }
      .left-col, .right-col { align-items: center; }
      .right-col { width: 100%; }
    }
    @media (max-width: 768px) {
      .header { padding: 20px 30px; }
      .logo { left: 30px; width: 50px; height: 50px; }
      .title { font-size: 20px; letter-spacing: 2px; }
      .wheel-title { font-size: 32px; letter-spacing: 2px; text-align: center; }
      .wheel-subtitle { font-size: 18px; text-align: center; }
      canvas { width: 350px !important; height: 350px !important; }
      .spin-button { padding: 15px 30px; font-size: 16px; min-width: 160px; }
      .result h3 { font-size: 24px; }
      .result .prize-name { font-size: 20px; }
      .result .redemption-code { font-size: 18px; letter-spacing: 2px; }
    }
  </style>
</head>
<body>
  <!-- Fondo de partículas -->
  <div class="floating-particles" id="particles"></div>

  <div class="header">
    <img src="logo.png" alt="CAT21 Logo" class="logo">
    <div class="title">Ruleta de Premios</div>
  </div>

  <div class="container">
    <div class="two-col">
      <!-- IZQUIERDA: Ruleta + Botón + Resultado -->
      <div class="left-col">
        <div class="wheel-container">
          <div class="wheel-glow"></div>
          <div class="wheel-pointer"><div class="pointer-arrow"></div></div>
          <canvas id="wheel" width="550" height="550" class="<?php echo $selected_prize ? 'canvas-hidden' : ''; ?>"></canvas>
        </div>

        <form method="POST" id="spinForm" style="<?php echo $selected_prize ? 'display: none;' : ''; ?>">
          <input type="hidden" name="spin" value="1">
          <button type="submit" class="spin-button" id="spinButton">✨ Girar Ruleta ✨</button>
        </form>

        <?php if ($selected_prize && $redemption_code): ?>
          <div class="result" id="resultSection">
            <h3>🎉 ¡FELICIDADES! 🎉</h3>
            <div class="prize-name"><?php echo htmlspecialchars($selected_prize); ?></div>
            <div class="code-section">
              <p style="color:#ffffff; margin-bottom: 10px;">Código de Canje:</p>
              <div class="redemption-code"><?php echo htmlspecialchars($redemption_code); ?></div>
            </div>
            <div style="display:flex; gap:12px; flex-wrap: wrap; justify-content:center;">
              <button class="spin-button" onclick="copyCode('<?php echo htmlspecialchars($redemption_code); ?>')" id="copyButton">📋 Copiar Código</button>
              <a href="inicio.php" class="spin-button" style="text-decoration:none; display:inline-block;">🚀 Continuar</a>
            </div>
            <p style="color:#ffffff; margin-top: 16px; font-size: 16px; text-align:center;">
              ¡Hay más premios disponibles para ti! ¡Te esperamos en el reto!
            </p>
          </div>
        <?php endif; ?>
      </div>

      <!-- DERECHA: Título/Subtítulo + Lista de premios -->
      <div class="right-col">
        <div class="wheel-section">
          <h1 class="wheel-title">¡Gira y Gana!</h1>
          <p class="wheel-subtitle">Tu premio te está esperando</p>
        </div>

        <div class="panel">
          <h2>Premios posibles</h2>
          <ul class="prize-list">
            <?php foreach ($prizes as $p): ?>
              <li><span class="prize-dot"></span><?php echo htmlspecialchars($p); ?></li>
            <?php endforeach; ?>
          </ul>
          <p style="color:#fff; opacity:.9; margin-top:8px">
            Gira una vez. Al finalizar el giro, tu código aparecerá junto a la ruleta.
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-minimal">
    <p>© 2025 Todos los derechos reservados</p>
  </div>

  <script>
    // Partículas
    function createParticles() {
      const particlesContainer = document.getElementById('particles');
      const colors = ['#FFD700','#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7'];
      for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random()*100 + '%';
        p.style.backgroundColor = colors[Math.floor(Math.random()*colors.length)];
        p.style.animationDelay = Math.random()*6 + 's';
        p.style.animationDuration = (Math.random()*4+4) + 's';
        particlesContainer.appendChild(p);
      }
    }

    // Confeti
    function createConfetti() {
      const colors = ['#FFD700','#FF6B6B','#4ECDC4','#45B7D1','#96CEB4'];
      for (let i = 0; i < 50; i++) {
        const c = document.createElement('div');
        c.style.position='fixed'; c.style.left = Math.random()*window.innerWidth+'px';
        c.style.top='-10px'; c.style.width='10px'; c.style.height='10px';
        c.style.backgroundColor = colors[Math.floor(Math.random()*colors.length)];
        c.style.zIndex='1000'; c.style.animation=`confettiFall ${Math.random()*3+2}s linear forwards`;
        document.body.appendChild(c);
        setTimeout(()=>c.remove(), 5000);
      }
    }

    // Animaciones extra
    const style = document.createElement('style');
    style.textContent = `
      @keyframes confettiFall { to { transform: translateY(calc(100vh + 20px)) rotate(720deg); } }
      @keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-2px)} 75%{transform:translateX(2px)} }
    `;
    document.head.appendChild(style);

    // Canvas ruleta
    const canvas = document.getElementById('wheel');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const prizes = <?php echo json_encode($prizes); ?>;
    const selectedPrizeIndex = <?php echo $selected_prize_index; ?>;
    const numSegments = prizes.length;
    const anglePerSegment = 2*Math.PI/numSegments;
    let currentAngle = 0;
    let spinning = false;

    const segmentColors = [
      {start:'#FF6B6B', end:'#FF8E53', accent:'#FFD700'},
      {start:'#4ECDC4', end:'#44A08D', accent:'#A8E6CF'},
      {start:'#FFD93D', end:'#FF8B94', accent:'#FF6B9D'},
      {start:'#6C5CE7', end:'#74B9FF', accent:'#A29BFE'},
      {start:'#FD79A8', end:'#FDCB6E', accent:'#E17055'}
    ];

    function drawWheel() {
      if (!ctx) return;
      ctx.clearRect(0,0,canvas.width,canvas.height);
      const centerX = canvas.width/2, centerY = canvas.height/2;
      const radius = Math.min(centerX,centerY)-20;

      for (let i=0;i<numSegments;i++){
        const startAngle = anglePerSegment*i;
        const endAngle   = anglePerSegment*(i+1);
        const gradient = ctx.createRadialGradient(centerX,centerY,0,centerX,centerY,radius);
        gradient.addColorStop(0, segmentColors[i].accent);
        gradient.addColorStop(0.6, segmentColors[i].start);
        gradient.addColorStop(1, segmentColors[i].end);

        ctx.beginPath(); ctx.moveTo(centerX,centerY);
        ctx.arc(centerX,centerY,radius,startAngle,endAngle); ctx.closePath();
        ctx.fillStyle = gradient; ctx.fill();

        const shadowGradient = ctx.createRadialGradient(centerX,centerY,radius*0.8,centerX,centerY,radius);
        shadowGradient.addColorStop(0,'rgba(0,0,0,0)'); shadowGradient.addColorStop(1,'rgba(0,0,0,0.3)');
        ctx.fillStyle = shadowGradient; ctx.fill();

        ctx.beginPath(); ctx.moveTo(centerX,centerY);
        ctx.lineTo(centerX+Math.cos(startAngle)*radius, centerY+Math.sin(startAngle)*radius);
        ctx.strokeStyle='rgba(255,255,255,0.8)'; ctx.lineWidth=3; ctx.stroke();

        // Texto
        ctx.save(); ctx.translate(centerX,centerY); ctx.rotate(startAngle+anglePerSegment/2);
        const text = prizes[i], words = text.split(' '), maxWidth = radius*0.6;
        let lines=[], line=words[0];
        for (let j=1;j<words.length;j++){
          const test = line+' '+words[j];
          if (ctx.measureText(test).width > maxWidth && line!==''){ lines.push(line); line=words[j]; }
          else { line=test; }
        }
        lines.push(line);
        const lineHeight=24, totalH = lines.length*lineHeight, startY = -totalH/2 + lineHeight/2;
        for (let k=0;k<lines.length;k++){
          const y = startY + k*lineHeight;
          ctx.fillStyle='rgba(0,0,0,0.8)'; ctx.font='bold 20px Segoe UI'; ctx.textAlign='center'; ctx.textBaseline='middle';
          ctx.fillText(lines[k], radius*0.65, y+2);
          ctx.fillStyle='#FFFFFF'; ctx.fillText(lines[k], radius*0.65, y);
        }
        ctx.restore();
      }

      // Centro
      const cg = ctx.createRadialGradient(centerX,centerY,0,centerX,centerY,45);
      cg.addColorStop(0,'#FFFFFF'); cg.addColorStop(0.7,'#F0F0F0'); cg.addColorStop(1,'#CCCCCC');
      ctx.beginPath(); ctx.arc(centerX,centerY,45,0,2*Math.PI); ctx.fillStyle=cg; ctx.fill();
      ctx.strokeStyle='#333333'; ctx.lineWidth=5; ctx.stroke();

      ctx.fillStyle='#333333'; ctx.font='bold 18px Segoe UI'; ctx.textAlign='center'; ctx.textBaseline='middle';
      ctx.fillText('SPIN', centerX, centerY);
    }

    function spinWheel() {
      if (spinning) return;
      spinning = true;
      const spinBtn = document.getElementById('spinButton');
      if (spinBtn) {
        spinBtn.disabled = true;
        spinBtn.innerHTML = '🎯 Girando...';
      }
      document.body.style.animation = 'shake 0.5s ease-in-out infinite';

      const spinTime = 4000, fadeStartTime = 1000;
      const totalSpinAngle = Math.random()*360 + (Math.floor(Math.random()*3)+4)*360; // 4-6 vueltas
      const startTime = Date.now(), startAngle = currentAngle;
      const easeOutQuart = t => 1 - Math.pow(1-t,4);

      function animate(){
        const elapsed = Date.now()-startTime;
        const progress = Math.min(elapsed/spinTime, 1);
        const eased = easeOutQuart(progress);
        currentAngle = startAngle + totalSpinAngle*eased;

        if (elapsed >= fadeStartTime){
          const fp = (elapsed - fadeStartTime) / (spinTime - fadeStartTime);
          if (canvas) {
            canvas.style.opacity = 1 - fp*0.8;
            canvas.style.filter = `blur(${fp*10}px)`;
          }
        }

        if (ctx && canvas) {
          ctx.save(); ctx.translate(canvas.width/2,canvas.height/2);
          ctx.rotate((currentAngle*Math.PI)/180);
          ctx.translate(-canvas.width/2,-canvas.height/2);
          drawWheel(); ctx.restore();
        }

        if (progress < 1) requestAnimationFrame(animate);
        else {
          spinning = false; document.body.style.animation = '';
          if (selectedPrizeIndex === -1) {
            // Envío de form para que PHP genere el premio/código
            const form = document.getElementById('spinForm');
            if (form) form.submit();
          } else {
            // Este camino solo se usaría si ya supiéramos el índice
            setTimeout(()=>{
              if (canvas) canvas.classList.add('canvas-hidden');
              const form = document.getElementById('spinForm');
              if (form) form.style.display='none';
              createConfetti();
            },200);
          }
        }
      }
      requestAnimationFrame(animate);
    }

    function copyCode(code){
      navigator.clipboard.writeText(code).then(()=>{
        const btn = document.getElementById('copyButton');
        if (btn) {
          btn.innerHTML='✅ ¡Copiado!';
          setTimeout(()=>{ btn.innerHTML='📋 Copiar Código'; },2000);
        }
      }).catch(err=>{
        console.error('Error al copiar:', err);
        alert('Error al copiar el código');
      });
    }

    // Eventos
    const form = document.getElementById('spinForm');
    if (form) {
      form.addEventListener('submit', function(e){ e.preventDefault(); spinWheel(); });
    }

    // Init
    createParticles();
    if (ctx) drawWheel();

    // Si ya hay resultado, lanzar confeti
    <?php if ($selected_prize && $redemption_code): ?>
      createConfetti();
    <?php endif; ?>
  </script>
</body>
</html>
