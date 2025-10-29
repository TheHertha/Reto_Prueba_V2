<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

//comentario para comentar que no joda el deploy

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
    echo "<div style='text-align: center; color: #FF0000; margin: 60px;'>Error de base de datos.</div>";
    exit;
}

$prizes = [
    "Libro de Bienestar",
    "Batido Nutricional Gratis",
    "Par de Zapatos Deportivos",
    "Kit de Snacks Saludables",
    "Suplemento de Proteína"
];

$redemption_code = '';
$selected_prize = '';
$selected_prize_index = -1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spin'])) {
    $selected_prize_index = array_rand($prizes);
    $selected_prize = $prizes[$selected_prize_index];
    $redemption_code = strtoupper(bin2hex(random_bytes(4)));

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET has_spun = 1, redemption_code = ?, prize = ? WHERE id = ?");
        $stmt->execute([$redemption_code, $selected_prize, $user_id]);
        $_SESSION['prize'] = $selected_prize;
        $_SESSION['redemption_code'] = $redemption_code;
    } catch (PDOException $e) {
        echo "<div style='text-align: center; color: #FF0000; margin: 60px;'>Error al guardar premio.</div>";
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
    :root{
      --red:#e10600;
      --yellow:#ffd504;
      --black:#000;
      --white:#fff;
      --wheel-size:min(84vmin, 560px);
      --radius-shadow: 28px;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      min-height:100svh;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      background:
        radial-gradient(80% 50% at 50% 0%, rgba(255,255,255,.10), transparent 60%),
        linear-gradient(180deg, #111 0%, #000 100%);
      color:var(--white);
      display:grid;
      place-items:center;
      overflow:hidden;
      padding:16px;
    }

    .stage{
      width:100%;
      max-width:720px;
      display:grid;
      justify-items:center;
      gap:18px;
      position:relative;
    }

    /* Contenedor fijo para ruleta + premio (mismo espacio) */
    .wheel-container{
      position:relative;
      width:var(--wheel-size);
      height:var(--wheel-size);
      display:grid;
      place-items:center;
    }

    .pointer{
      position:absolute;
      top:-8px; left:50%; transform:translateX(-50%);
      width:0;height:0;
      border-left:18px solid transparent;
      border-right:18px solid transparent;
      border-top:34px solid var(--red);
      filter: drop-shadow(0 6px 10px rgba(0,0,0,.6));
      z-index:10;
      transition: opacity .4s ease, transform .4s ease;
    }
    .pointer::after{
      content:"";
      position:absolute; left:-14px; top:-28px;
      width:0;height:0;
      border-left:14px solid transparent;
      border-right:14px solid transparent;
      border-top:26px solid #b20500;
    }

    canvas{
      width:100%; height:100%;
      border-radius:50%;
      background: radial-gradient(circle at 50% 50%, #111 0%, #000 60%);
      box-shadow:
        inset 0 0 0 6px rgba(255,255,255,.04),
        0 0 0 10px rgba(255,255,255,.03),
        0 22px 80px rgba(0,0,0,.65);
      transition: opacity .5s ease, filter .5s ease, transform .5s ease;
    }
    .canvas-hidden{ opacity:0; filter:blur(12px); transform:scale(0.92); pointer-events:none }

    .btn{
      appearance:none; border:none; cursor:pointer;
      background: linear-gradient(180deg, var(--red), #b20500);
      color:var(--white);
      padding:14px 28px; border-radius:999px;
      font-weight:800; letter-spacing:.06em; text-transform:uppercase;
      box-shadow: 0 8px 18px rgba(225,6,0,.35), inset 0 0 0 2px rgba(255,255,255,.08);
      transition: all .15s ease;
    }
    .btn:hover{ transform:translateY(-2px); box-shadow:0 12px 26px rgba(225,6,0,.45), inset 0 0 0 2px rgba(255,255,255,.12) }
    .btn:disabled{ opacity:.7; cursor:not-allowed }

    /* Resultado en el mismo espacio */
    .result{
      position:absolute; inset:0;
      display:grid; place-items:center;
      opacity:0; pointer-events:none;
      transition: opacity .5s ease;
      background: rgba(0,0,0,.85);
      border-radius:50%;
      padding:20px;
    }
    .result.show{ opacity:1; pointer-events:auto }

    .result-card{
      background: #0c0c0c;
      border:1px solid rgba(255,255,255,.12);
      border-radius:18px;
      padding:22px;
      max-width:80%;
      text-align:center;
      box-shadow: 0 16px 60px rgba(0,0,0,.6);
      animation: pop .45s ease both;
    }
    @keyframes pop{ from{transform:scale(0.8); opacity:0} to{transform:scale(1); opacity:1} }

    .result h3{ font-size:clamp(20px, 4vmin, 28px); color:var(--yellow); margin-bottom:8px }
    .prize{ font-size:clamp(18px, 3.2vmin, 24px); font-weight:900; margin:8px 0 }
    .code{
      letter-spacing:.24em; font-weight:900; font-size:clamp(16px, 2.6vmin, 22px);
      background:#111; padding:12px 16px; border-radius:12px;
      border:1px solid rgba(255,255,255,.12); margin:12px 0
    }
    .row{ display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:12px }
  </style>
</head>
<body>

  <div class="stage">
    <div class="wheel-container" id="wheelContainer">
      <div class="pointer" id="pointer"></div>
      <canvas id="wheel" width="560" height="560" class="<?php echo $selected_prize ? 'canvas-hidden' : ''; ?>"></canvas>

      <!-- Resultado en el mismo espacio -->
      <div class="result <?php echo $selected_prize ? 'show' : ''; ?>" id="result">
        <div class="result-card">
          <h3>¡Felicidades! 🎉</h3>
          <div class="prize"><?php echo htmlspecialchars($selected_prize) ?></div>
          <div class="code" id="code"><?php echo htmlspecialchars($redemption_code) ?></div>
          <div class="row">
            <button class="btn" id="copyButton">Copiar código</button>
            <a class="btn" href="inicio.php">Continuar</a>
          </div>
          <small style="opacity:.8; display:block; margin-top:12px">Canjea tu código con el equipo CAT21.</small>
        </div>
      </div>
    </div>

    <!-- Botón debajo -->
    <form method="POST" id="spinForm" style="<?php echo $selected_prize ? 'display:none' : 'display:block' ?>">
      <input type="hidden" name="spin" value="1">
      <button type="submit" id="spinButton" class="btn">Girar ruleta</button>
    </form>
  </div>

  <script>
    const canvas = document.getElementById('wheel');
    const ctx = canvas.getContext('2d');
    const prizes = <?php echo json_encode($prizes, JSON_UNESCAPED_UNICODE); ?>;
    const N = prizes.length;
    const TAU = Math.PI * 2;
    const anglePer = TAU / N;

    let currentAngle = 0;
    let spinning = false;

    function fitCanvas() {
      const rect = canvas.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      canvas.width = Math.round(rect.width * dpr);
      canvas.height = Math.round(rect.height * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function drawWheel(angle = 0) {
      const w = canvas.width / (window.devicePixelRatio || 1);
      const cx = w / 2, cy = w / 2, r = (w / 2) - 10;

      ctx.clearRect(0, 0, w, w);
      ctx.save();
      ctx.translate(cx, cy);
      ctx.rotate(angle * Math.PI / 180);
      ctx.translate(-cx, -cy);

      for (let i = 0; i < N; i++) {
        const start = anglePer * i;
        const end = anglePer * (i + 1);
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, start, end);
        ctx.closePath();
        ctx.fillStyle = i % 2 === 0 ? '#e10600' : '#ffd504';
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(0,0,0,.6)';
        ctx.stroke();

        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(start + anglePer / 2);
        ctx.font = '700 16px system-ui';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = i % 2 === 0 ? '#fff' : '#000';
        ctx.shadowColor = 'rgba(0,0,0,.3)';
        ctx.shadowBlur = 3;
        ctx.fillText(prizes[i], r - 20, 0);
        ctx.restore();
      }

      // centro
      ctx.beginPath();
      ctx.arc(cx, cy, 42, 0, TAU);
      ctx.fillStyle = '#fff';
      ctx.fill();
      ctx.lineWidth = 6;
      ctx.strokeStyle = '#000';
      ctx.stroke();
      ctx.fillStyle = '#000';
      ctx.font = '900 16px system-ui';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('SPIN', cx, cy);

      ctx.restore();
    }

    function spin() {
      if (spinning) return;
      spinning = true;
      const btn = document.getElementById('spinButton');
      btn.disabled = true;
      btn.textContent = 'Girando…';

      const duration = 4200;
      const start = performance.now();
      const startAngle = currentAngle;
      const totalSpins = 4 + Math.floor(Math.random() * 3);
      const finalAngle = totalSpins * 360 + Math.random() * 360;

      function frame(now) {
        const elapsed = now - start;
        const t = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - t, 4);
        currentAngle = startAngle + finalAngle * eased;

        if (elapsed > duration * 0.7) {
          const bf = (elapsed - duration * 0.7) / (duration * 0.3);
          canvas.style.filter = `blur(${bf * 10}px)`;
          canvas.style.opacity = 1 - bf * 0.8;
        }

        drawWheel(currentAngle);

        if (t < 1) {
          requestAnimationFrame(frame);
        } else {
          setTimeout(() => {
            document.getElementById('wheelContainer').querySelectorAll('.pointer, canvas').forEach(el => {
              el.style.opacity = '0';
              el.style.transition = 'opacity .4s ease';
            });
            canvas.classList.add('canvas-hidden');
            document.getElementById('result').classList.add('show');
            document.getElementById('spinForm').style.display = 'none';
          }, 300);

          setTimeout(() => document.getElementById('spinForm').submit(), 700);
        }
      }
      requestAnimationFrame(frame);
    }

    // Eventos
    document.getElementById('spinForm').addEventListener('submit', e => {
      e.preventDefault();
      spin();
    });

    document.getElementById('copyButton')?.addEventListener('click', async () => {
      const code = document.getElementById('code').textContent.trim();
      try {
        await navigator.clipboard.writeText(code);
        const btn = document.getElementById('copyButton');
        btn.textContent = '¡Copiado!';
        setTimeout(() => btn.textContent = 'Copiar código', 1600);
      } catch {
        alert('No se pudo copiar');
      }
    });

    // Init
    fitCanvas();
    drawWheel();
    window.addEventListener('resize', () => {
      fitCanvas();
      drawWheel(currentAngle);
    });

    <?php if ($selected_prize): ?>
      document.getElementById('pointer').style.opacity = '0';
      document.getElementById('wheel').classList.add('canvas-hidden');
      document.getElementById('result').classList.add('show');
      document.getElementById('spinForm').style.display = 'none';
    <?php endif; ?>
  </script>
</body>
</html>