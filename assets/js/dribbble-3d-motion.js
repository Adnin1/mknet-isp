/**
 * Dribbble-Grade 3D Motion & Interactive Micro-Interactions
 * Includes real-time 3D card tilt, button ripple clicks, floating parallax & ambient glowing light orbs
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Real-time 3D Perspective Tilt on Hover for Cards & Bento Boxes
  const tiltElements = document.querySelectorAll('.card, .pkg-card, .stat-card, .admin-card, .grid > div, .bento-box');
  
  tiltElements.forEach(card => {
    card.style.transition = 'transform 0.15s ease-out, box-shadow 0.3s ease, border-color 0.3s ease';
    card.style.transformStyle = 'preserve-3d';
    
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;
      
      const rotateX = ((y - centerY) / centerY) * -10; // tilt up/down up to 10deg
      const rotateY = ((x - centerX) / centerX) * 10;  // tilt left/right up to 10deg
      
      card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(12px) scale(1.02)`;
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px) scale(1)';
    });
  });

  // 2. Ripple Click Effect on All Buttons
  const buttons = document.querySelectorAll('.btn, button, input[type="submit"], a.btn, .action-btn');
  
  buttons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      const circle = document.createElement('span');
      const diameter = Math.max(this.clientWidth, this.clientHeight);
      const radius = diameter / 2;
      const rect = this.getBoundingClientRect();
      
      circle.style.width = circle.style.height = `${diameter}px`;
      circle.style.left = `${e.clientX - rect.left - radius}px`;
      circle.style.top = `${e.clientY - rect.top - radius}px`;
      circle.classList.add('dribbble-ripple');
      
      const existingRipple = this.querySelector('.dribbble-ripple');
      if (existingRipple) {
        existingRipple.remove();
      }
      
      this.appendChild(circle);
      setTimeout(() => circle.remove(), 600);
    });
  });

  // 3. Dynamic Ambient Glowing Backdrop Orbs
  if (!document.querySelector('.ambient-orb-container')) {
    const orbContainer = document.createElement('div');
    orbContainer.className = 'ambient-orb-container';
    orbContainer.innerHTML = `
      <div class="ambient-orb orb-1"></div>
      <div class="ambient-orb orb-2"></div>
      <div class="ambient-orb orb-3"></div>
    `;
    document.body.prepend(orbContainer);
  }
});
