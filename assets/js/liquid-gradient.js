// TouchTexture — registra el rastro del ratón/táctil para el efecto de distorsión
class TouchTexture {
  constructor() {
    this.size = 64;
    this.width = this.height = this.size;
    this.maxAge = 64;
    this.radius = 0.25 * this.size;
    this.speed = 1 / this.maxAge;
    this.trail = [];
    this.last = null;
    this.initTexture();
  }

  initTexture() {
    this.canvas = document.createElement("canvas");
    this.canvas.width = this.width;
    this.canvas.height = this.height;
    this.ctx = this.canvas.getContext("2d");
    this.ctx.fillStyle = "black";
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    this.texture = new THREE.Texture(this.canvas);
  }

  update() {
    this.clear();
    const speed = this.speed;
    for (let i = this.trail.length - 1; i >= 0; i--) {
      const point = this.trail[i];
      const f = point.force * speed * (1 - point.age / this.maxAge);
      point.x += point.vx * f;
      point.y += point.vy * f;
      point.age++;
      if (point.age > this.maxAge) {
        this.trail.splice(i, 1);
      } else {
        this.drawPoint(point);
      }
    }
    this.texture.needsUpdate = true;
  }

  clear() {
    this.ctx.fillStyle = "black";
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
  }

  addTouch(point) {
    let force = 0, vx = 0, vy = 0;
    const last = this.last;
    if (last) {
      const dx = point.x - last.x;
      const dy = point.y - last.y;
      if (dx === 0 && dy === 0) return;
      const dd = dx * dx + dy * dy;
      const d = Math.sqrt(dd);
      vx = dx / d;
      vy = dy / d;
      force = Math.min(dd * 20000, 2.0);
    }
    this.last = { x: point.x, y: point.y };
    this.trail.push({ x: point.x, y: point.y, age: 0, force, vx, vy });
  }

  drawPoint(point) {
    const pos = { x: point.x * this.width, y: (1 - point.y) * this.height };
    let intensity = 1;
    if (point.age < this.maxAge * 0.3) {
      intensity = Math.sin((point.age / (this.maxAge * 0.3)) * (Math.PI / 2));
    } else {
      const t = 1 - (point.age - this.maxAge * 0.3) / (this.maxAge * 0.7);
      intensity = -t * (t - 2);
    }
    intensity *= point.force;
    const radius = this.radius;
    const color = `${((point.vx + 1) / 2) * 255}, ${((point.vy + 1) / 2) * 255}, ${intensity * 255}`;
    const offset = this.size * 5;
    this.ctx.shadowOffsetX = offset;
    this.ctx.shadowOffsetY = offset;
    this.ctx.shadowBlur = radius;
    this.ctx.shadowColor = `rgba(${color},${0.2 * intensity})`;
    this.ctx.beginPath();
    this.ctx.fillStyle = "rgba(255,0,0,1)";
    this.ctx.arc(pos.x - offset, pos.y - offset, radius, 0, Math.PI * 2);
    this.ctx.fill();
  }
}

// GradientBackground — malla de shaders WebGL del gradiente animado
class GradientBackground {
  constructor(sceneManager) {
    this.sceneManager = sceneManager;
    this.mesh = null;
    this.uniforms = {
      uTime:          { value: 0 },
      uResolution:    { value: new THREE.Vector2(window.innerWidth, window.innerHeight) },
      uColor1:        { value: new THREE.Vector3(0.133, 0.827, 0.933) }, // #22D3EE cyan
      uColor2:        { value: new THREE.Vector3(0.118, 0.106, 0.294) }, // #1E1B4B dark indigo
      uColor3:        { value: new THREE.Vector3(0.388, 0.400, 0.945) }, // #6366F1 indigo
      uColor4:        { value: new THREE.Vector3(0.192, 0.180, 0.506) }, // #312E81 deep indigo
      uColor5:        { value: new THREE.Vector3(0.133, 0.827, 0.933) }, // #22D3EE cyan
      uColor6:        { value: new THREE.Vector3(0.388, 0.400, 0.945) }, // #6366F1 indigo
      uSpeed:         { value: 1.5 },
      uIntensity:     { value: 1.8 },
      uTouchTexture:  { value: null },
      uGrainIntensity:{ value: 0.08 },
      uZoom:          { value: 1.0 },
      uDarkNavy:      { value: new THREE.Vector3(0.118, 0.106, 0.294) }, // #1E1B4B
      uGradientSize:  { value: 0.45 },
      uGradientCount: { value: 12.0 },
      uColor1Weight:  { value: 1.2 },
      uColor2Weight:  { value: 1.0 }
    };
  }

  init() {
    const viewSize = this.sceneManager.getViewSize();
    const geometry = new THREE.PlaneGeometry(viewSize.width, viewSize.height, 1, 1);
    const material = new THREE.ShaderMaterial({
      uniforms: this.uniforms,
      vertexShader: `
        varying vec2 vUv;
        void main() {
          gl_Position = projectionMatrix * modelViewMatrix * vec4(position.xyz, 1.);
          vUv = uv;
        }
      `,
      fragmentShader: `
        uniform float uTime;
        uniform vec2  uResolution;
        uniform vec3  uColor1, uColor2, uColor3, uColor4, uColor5, uColor6;
        uniform float uSpeed, uIntensity;
        uniform sampler2D uTouchTexture;
        uniform float uGrainIntensity, uZoom;
        uniform vec3  uDarkNavy;
        uniform float uGradientSize, uGradientCount, uColor1Weight, uColor2Weight;
        varying vec2 vUv;
        #define PI 3.14159265359

        float grain(vec2 uv, float time) {
          vec2 g = uv * uResolution * 0.5;
          return fract(sin(dot(g + time, vec2(12.9898, 78.233))) * 43758.5453) * 2.0 - 1.0;
        }

        vec3 getGradientColor(vec2 uv, float time) {
          float r = uGradientSize;
          vec2 c1  = vec2(0.5 + sin(time*uSpeed*0.40)*0.40, 0.5 + cos(time*uSpeed*0.50)*0.40);
          vec2 c2  = vec2(0.5 + cos(time*uSpeed*0.60)*0.50, 0.5 + sin(time*uSpeed*0.45)*0.50);
          vec2 c3  = vec2(0.5 + sin(time*uSpeed*0.35)*0.45, 0.5 + cos(time*uSpeed*0.55)*0.45);
          vec2 c4  = vec2(0.5 + cos(time*uSpeed*0.50)*0.40, 0.5 + sin(time*uSpeed*0.40)*0.40);
          vec2 c5  = vec2(0.5 + sin(time*uSpeed*0.70)*0.35, 0.5 + cos(time*uSpeed*0.60)*0.35);
          vec2 c6  = vec2(0.5 + cos(time*uSpeed*0.45)*0.50, 0.5 + sin(time*uSpeed*0.65)*0.50);
          vec2 c7  = vec2(0.5 + sin(time*uSpeed*0.55)*0.38, 0.5 + cos(time*uSpeed*0.48)*0.42);
          vec2 c8  = vec2(0.5 + cos(time*uSpeed*0.65)*0.36, 0.5 + sin(time*uSpeed*0.52)*0.44);
          vec2 c9  = vec2(0.5 + sin(time*uSpeed*0.42)*0.41, 0.5 + cos(time*uSpeed*0.58)*0.39);
          vec2 c10 = vec2(0.5 + cos(time*uSpeed*0.48)*0.37, 0.5 + sin(time*uSpeed*0.62)*0.43);
          vec2 c11 = vec2(0.5 + sin(time*uSpeed*0.68)*0.33, 0.5 + cos(time*uSpeed*0.44)*0.46);
          vec2 c12 = vec2(0.5 + cos(time*uSpeed*0.38)*0.39, 0.5 + sin(time*uSpeed*0.56)*0.41);

          float i1  = 1.0 - smoothstep(0.0, r, length(uv-c1));
          float i2  = 1.0 - smoothstep(0.0, r, length(uv-c2));
          float i3  = 1.0 - smoothstep(0.0, r, length(uv-c3));
          float i4  = 1.0 - smoothstep(0.0, r, length(uv-c4));
          float i5  = 1.0 - smoothstep(0.0, r, length(uv-c5));
          float i6  = 1.0 - smoothstep(0.0, r, length(uv-c6));
          float i7  = 1.0 - smoothstep(0.0, r, length(uv-c7));
          float i8  = 1.0 - smoothstep(0.0, r, length(uv-c8));
          float i9  = 1.0 - smoothstep(0.0, r, length(uv-c9));
          float i10 = 1.0 - smoothstep(0.0, r, length(uv-c10));
          float i11 = 1.0 - smoothstep(0.0, r, length(uv-c11));
          float i12 = 1.0 - smoothstep(0.0, r, length(uv-c12));

          vec2 ru1 = uv - 0.5;
          float a1 = time*uSpeed*0.15;
          ru1 = vec2(ru1.x*cos(a1)-ru1.y*sin(a1), ru1.x*sin(a1)+ru1.y*cos(a1)) + 0.5;
          vec2 ru2 = uv - 0.5;
          float a2 = -time*uSpeed*0.12;
          ru2 = vec2(ru2.x*cos(a2)-ru2.y*sin(a2), ru2.x*sin(a2)+ru2.y*cos(a2)) + 0.5;

          float ri1 = 1.0 - smoothstep(0.0, 0.8, length(ru1-0.5));
          float ri2 = 1.0 - smoothstep(0.0, 0.8, length(ru2-0.5));

          vec3 col = vec3(0.0);
          col += uColor1*(0.55+0.45*sin(time*uSpeed    ))*i1 *uColor1Weight;
          col += uColor2*(0.55+0.45*cos(time*uSpeed*1.2))*i2 *uColor2Weight;
          col += uColor3*(0.55+0.45*sin(time*uSpeed*0.8))*i3 *uColor1Weight;
          col += uColor4*(0.55+0.45*cos(time*uSpeed*1.3))*i4 *uColor2Weight;
          col += uColor5*(0.55+0.45*sin(time*uSpeed*1.1))*i5 *uColor1Weight;
          col += uColor6*(0.55+0.45*cos(time*uSpeed*0.9))*i6 *uColor2Weight;
          if (uGradientCount > 6.0) {
            col += uColor1*(0.55+0.45*sin(time*uSpeed*1.4))*i7 *uColor1Weight;
            col += uColor2*(0.55+0.45*cos(time*uSpeed*1.5))*i8 *uColor2Weight;
            col += uColor3*(0.55+0.45*sin(time*uSpeed*1.6))*i9 *uColor1Weight;
            col += uColor4*(0.55+0.45*cos(time*uSpeed*1.7))*i10*uColor2Weight;
          }
          if (uGradientCount > 10.0) {
            col += uColor5*(0.55+0.45*sin(time*uSpeed*1.8))*i11*uColor1Weight;
            col += uColor6*(0.55+0.45*cos(time*uSpeed*1.9))*i12*uColor2Weight;
          }
          col += mix(uColor1, uColor3, ri1) * 0.45 * uColor1Weight;
          col += mix(uColor2, uColor4, ri2) * 0.40 * uColor2Weight;

          col = clamp(col, vec3(0.0), vec3(1.0)) * uIntensity;
          float lum = dot(col, vec3(0.299, 0.587, 0.114));
          col = mix(vec3(lum), col, 1.35);
          col = pow(col, vec3(0.92));

          float br1 = length(col);
          col = mix(uDarkNavy, col, max(br1*1.2, 0.15));

          float maxB = 1.0, br = length(col);
          if (br > maxB) col *= maxB/br;
          return col;
        }

        void main() {
          vec2 uv = vUv;
          vec4 touch = texture2D(uTouchTexture, uv);
          float vx = -(touch.r*2.0-1.0), vy = -(touch.g*2.0-1.0), ti = touch.b;
          uv.x += vx*0.8*ti;
          uv.y += vy*0.8*ti;
          float dist = length(uv - vec2(0.5));
          uv += vec2(sin(dist*20.0-uTime*3.0)*0.04*ti + sin(dist*15.0-uTime*2.0)*0.03*ti);

          vec3 col = getGradientColor(uv, uTime);
          col += grain(uv, uTime) * uGrainIntensity;
          float ts = uTime*0.5;
          col.r += sin(ts)*0.02; col.g += cos(ts*1.4)*0.02; col.b += sin(ts*1.2)*0.02;

          float br2 = length(col);
          col = mix(uDarkNavy, col, max(br2*1.2, 0.15));
          col = clamp(col, vec3(0.0), vec3(1.0));
          float maxB2 = 1.0, br3 = length(col);
          if (br3 > maxB2) col *= maxB2/br3;

          gl_FragColor = vec4(col, 1.0);
        }
      `
    });
    this.mesh = new THREE.Mesh(geometry, material);
    this.mesh.position.z = 0;
    this.sceneManager.scene.add(this.mesh);
  }

  update(delta) {
    if (this.uniforms.uTime) this.uniforms.uTime.value += delta;
  }

  onResize(width, height) {
    const vs = this.sceneManager.getViewSize();
    if (this.mesh) {
      this.mesh.geometry.dispose();
      this.mesh.geometry = new THREE.PlaneGeometry(vs.width, vs.height, 1, 1);
    }
    if (this.uniforms.uResolution) this.uniforms.uResolution.value.set(width, height);
  }
}

// App — orquesta el renderer, la escena y el bucle de animación
class App {
  constructor() {
    this.renderer = new THREE.WebGLRenderer({
      antialias: true,
      powerPreference: "high-performance",
      alpha: false, stencil: false, depth: false
    });
    this.renderer.setSize(window.innerWidth, window.innerHeight);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    this.renderer.setAnimationLoop(null);
    // Inserta el canvas al principio del DOM para que quede detrás de todos los elementos
    document.body.prepend(this.renderer.domElement);
    this.renderer.domElement.id = "webGLApp";
    // Fuerza estilos inline para que el canvas sea siempre fondo fijo a pantalla completa
    const cs = this.renderer.domElement.style;
    cs.position = "fixed";
    cs.top = "0"; cs.left = "0";
    cs.width = "100%"; cs.height = "100%";
    cs.zIndex = "1";

    this.camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 10000);
    this.camera.position.z = 50;
    this.scene = new THREE.Scene();
    this.scene.background = new THREE.Color(0x1E1B4B);
    this.clock = new THREE.Clock();

    this.touchTexture = new TouchTexture();
    this.gradientBackground = new GradientBackground(this);
    this.gradientBackground.uniforms.uTouchTexture.value = this.touchTexture.texture;

    this.init();
  }

  init() {
    this.gradientBackground.init();
    this.render();
    this.tick();
    window.addEventListener("resize",    ()  => this.onResize());
    window.addEventListener("mousemove", (ev) => this.onMouseMove(ev));
    window.addEventListener("touchmove", (ev) => this.onTouchMove(ev));
    document.addEventListener("visibilitychange", () => { if (!document.hidden) this.render(); });
  }

  onTouchMove(ev) { this.onMouseMove({ clientX: ev.touches[0].clientX, clientY: ev.touches[0].clientY }); }
  onMouseMove(ev) {
    this.touchTexture.addTouch({ x: ev.clientX / window.innerWidth, y: 1 - ev.clientY / window.innerHeight });
  }

  getViewSize() {
    const h = Math.abs(this.camera.position.z * Math.tan((this.camera.fov * Math.PI / 180) / 2) * 2);
    return { width: h * this.camera.aspect, height: h };
  }

  update(delta) { this.touchTexture.update(); this.gradientBackground.update(delta); }

  render() {
    const delta = Math.min(this.clock.getDelta(), 0.1);
    this.renderer.render(this.scene, this.camera);
    this.update(delta);
  }

  tick() { this.render(); requestAnimationFrame(() => this.tick()); }

  onResize() {
    this.camera.aspect = window.innerWidth / window.innerHeight;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(window.innerWidth, window.innerHeight);
    this.gradientBackground.onResize(window.innerWidth, window.innerHeight);
  }
}

// Arranque
const app = new App();
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => app.render());
} else {
  setTimeout(() => app.render(), 0);
}
