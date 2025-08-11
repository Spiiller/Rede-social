document.addEventListener('DOMContentLoaded', () => {
  const debouncedLoadPosts = debounce(loadPosts, 300);
  debouncedLoadPosts();
  updateNavButtons();
});

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

async function loadPosts() {
  try {
    const response = await fetch('posts.php');
    if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
    const data = await response.json();

    const postsContainer = document.getElementById('posts-container');
    if (!postsContainer) return;
    postsContainer.innerHTML = '';

    if (data.posts && data.posts.length > 0) {
      data.posts.forEach(post => {
        const postElement = document.createElement('div');
        postElement.className = 'post';
        postElement.innerHTML = `
          <div class="post-header">
            <h2>${post.plantName}</h2>
          </div>
          <div class="gallery-container">
            <div class="gallery-images">
              ${post.images.map((img, index) => `
                <img src="${img}" class="gallery-image ${index === 0 ? 'active' : ''}" alt="Imagem do post">
              `).join('')}
            </div>
            ${post.images.length > 1 ? `
              <div class="gallery-nav">
                <button class="gallery-btn prev"><</button>
                <button class="gallery-btn next">></button>
              </div>
              <div class="gallery-dots">
                ${post.images.map((_, index) => `
                  <span class="gallery-dot ${index === 0 ? 'active' : ''}"></span>
                `).join('')}
              </div>
            ` : ''}
          </div>
          <div class="post-description">
            <p>${post.description}</p>
          </div>
          <div class="post-actions">
            <button class="action-btn like-btn ${post.liked ? 'active' : ''}" onclick="toggleLike(${post.id})">
              <i class="fas fa-heart"></i> <span>${post.likes || 0}</span>
            </button>
            <button class="action-btn comment-btn" onclick="toggleComments(${post.id})">
              <i class="fas fa-comment"></i>
            </button>
            <button class="action-btn save-btn ${post.saved ? 'active' : ''}" onclick="toggleSave(${post.id})">
              <i class="fas fa-bookmark"></i>
            </button>
          </div>
          <div class="comments-section" id="comments-${post.id}" style="display: none;">
            <h3>Comentários</h3>
            <div class="comments-list" id="comments-list-${post.id}"></div>
            <form class="comment-form" onsubmit="addComment(event, ${post.id})">
              <input type="text" placeholder="Adicionar comentário..." required>
              <button type="submit">Enviar</button>
            </form>
          </div>
        `;
        postsContainer.appendChild(postElement);
        setupGallery(postElement);
      });
    } else {
      postsContainer.innerHTML = '<p>Nenhum post encontrado.</p>';
    }
  } catch (error) {
    const postsContainer = document.getElementById('posts-container');
    if (postsContainer) {
      postsContainer.innerHTML = '<p>Erro ao carregar posts. Tente novamente.</p>';
    }
  }
}

function setupGallery(postElement) {
  const gallery = postElement.querySelector('.gallery-container');
  if (!gallery) return;

  const images = gallery.querySelectorAll('.gallery-image');
  const dots = gallery.querySelectorAll('.gallery-dot');
  const prevBtn = gallery.querySelector('.prev');
  const nextBtn = gallery.querySelector('.next');
  let currentIndex = 0;

  function showImage(index) {
    images.forEach((img, i) => {
      img.classList.toggle('active', i === index);
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === index);
    });
    currentIndex = index;
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      let newIndex = currentIndex - 1;
      if (newIndex < 0) newIndex = images.length - 1;
      showImage(newIndex);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      let newIndex = currentIndex + 1;
      if (newIndex >= images.length) newIndex = 0;
      showImage(newIndex);
    });
  }

  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => showImage(index));
  });
}

async function toggleLike(postId) {
  try {
    const response = await fetch('posts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=like&post_id=${postId}`
    });
    const data = await response.json();
    if (data.success) {
      loadPosts();
    }
  } catch (error) {}
}

async function toggleSave(postId) {
  try {
    const response = await fetch(`posts.php?post_id=${postId}`, { method: 'PATCH' });
    const data = await response.json();
    if (data.success) {
      loadPosts();
    }
  } catch (error) {}
}

async function addComment(event, postId) {
  event.preventDefault();
  const form = event.target;
  const commentInput = form.querySelector('input');
  const commentText = commentInput.value;

  try {
    const response = await fetch('posts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=comment&post_id=${postId}&comment=${encodeURIComponent(commentText)}`
    });
    const data = await response.json();
    if (data.success) {
      commentInput.value = '';
      loadComments(postId);
    }
  } catch (error) {}
}

async function loadComments(postId) {
  try {
    const response = await fetch(`posts.php?comments_for=${postId}`);
    const data = await response.json();
    const commentsList = document.getElementById(`comments-list-${postId}`);
    commentsList.innerHTML = '';

    if (data.comments && data.comments.length > 0) {
      data.comments.forEach(comment => {
        const commentElement = document.createElement('div');
        commentElement.className = 'comment';
        commentElement.innerHTML = `
          <strong>${comment.user}</strong>: ${comment.text}
          ${comment.canDelete ? `<button class="delete-comment-btn" onclick="deleteComment(${comment.id}, ${postId})">Excluir</button>` : ''}
        `;
        commentsList.appendChild(commentElement);
      });
    } else {
      commentsList.innerHTML = '<p>Nenhum comentário.</p>';
    }
  } catch (error) {}
}

async function deleteComment(commentId, postId) {
  try {
    const response = await fetch('posts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_comment&comment_id=${commentId}`
    });
    const data = await response.json();
    if (data.success) {
      loadComments(postId);
    }
  } catch (error) {}
}

function toggleComments(postId) {
  const commentsSection = document.getElementById(`comments-${postId}`);
  if (commentsSection.style.display === 'none') {
    commentsSection.style.display = 'block';
    loadComments(postId);
  } else {
    commentsSection.style.display = 'none';
  }
}

async function updateNavButtons() {
  try {
    const response = await fetch('profile.php');
    const data = await response.json();

    const navButtons = document.querySelector('.nav-buttons');
    navButtons.innerHTML = '';

    if (data.success && data.user) {
      // Meu Perfil
      const profileButton = document.createElement('a');
      profileButton.href = 'profile.html';
      profileButton.className = 'profile-button';
      profileButton.textContent = 'Meu Perfil';
      navButtons.appendChild(profileButton);

      // Painel Admin só se for admin!
      if (data.user.is_admin == 1 || data.user.is_admin === true) {
        const adminButton = document.createElement('a');
        adminButton.href = 'admin.html';
        adminButton.className = 'admin-button';
        adminButton.textContent = 'Painel Admin';
        navButtons.appendChild(adminButton);
      }

      // Logout
      const logoutButton = document.createElement('a');
      logoutButton.href = '#';
      logoutButton.className = 'logout-button';
      logoutButton.textContent = 'Sair';
      logoutButton.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
          const response = await fetch('auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=logout'
          });
          const data = await response.json();
          if (data.success) {
            window.location.href = 'login.html';
          }
        } catch (error) {}
      });
      navButtons.appendChild(logoutButton);
    } else {
      // Login
      const loginButton = document.createElement('a');
      loginButton.href = 'login.html';
      loginButton.className = 'login-button';
      loginButton.textContent = 'Login / Registrar';
      navButtons.appendChild(loginButton);
    }
  } catch (error) {}
}
