document.addEventListener('DOMContentLoaded', () => { 
  loadProfile();
  setupProfileForm();
  setupLogoutButton();
});

async function loadProfile() {
  try {
    const response = await fetch('profile.php');
    const data = await response.json();

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    if (!nameInput || !emailInput) return;

    if (data.success && data.user) {
      nameInput.value = data.user.name || '';
      emailInput.value = data.user.email || '';
      window.currentUserId = data.user.id;
      console.log('[Profile] currentUserId:', window.currentUserId); // DEBUG
      loadSavedPosts();
    } else {
      console.warn('[Profile] Usuário não carregado ou não autenticado.', data);
      window.currentUserId = null;
      // Não chame loadSavedPosts se não estiver autenticado!
      const savedPostsContainer = document.getElementById('saved-posts-container');
      if (savedPostsContainer) {
        savedPostsContainer.innerHTML = '<p class="no-saved-posts">Faça login para ver seus posts salvos.</p>';
      }
    }
  } catch (error) {
    console.error('[Profile] Erro ao carregar perfil:', error);
  }
}

async function loadSavedPosts() {
  if (!window.currentUserId) {
    console.warn('[Profile] currentUserId indefinido, não vai buscar posts salvos.');
    return;
  }
  console.log('[Profile] Buscando posts salvos do usuário', window.currentUserId); // DEBUG
  const savedPostsContainer = document.getElementById('saved-posts-container');
  try {
    const response = await fetch(`posts.php?saved_by=${window.currentUserId}`);
    const data = await response.json();
    savedPostsContainer.innerHTML = '';

    if (data.success && data.posts && data.posts.length > 0) {
      data.posts.forEach(post => {
        const postElement = document.createElement('div');
        postElement.className = 'saved-post';
        postElement.innerHTML = `
          <div class="saved-post-image">
            <img src="${(post.images && post.images[0]) ? post.images[0] : 'placeholder.jpg'}" alt="${post.plantName}">
          </div>
          <div class="saved-post-info">
            <h3>${post.plantName}</h3>
            <p>${post.description ? (post.description.substring(0, 50) + (post.description.length > 50 ? '...' : '')) : ''}</p>
            <button class="unsave-btn" onclick="unsavePost(${post.id})">Remover</button>
          </div>
        `;
        savedPostsContainer.appendChild(postElement);
      });
    } else {
      savedPostsContainer.innerHTML = '<p class="no-saved-posts">Nenhum post salvo encontrado.</p>';
    }
  } catch (error) {
    console.error('[Profile] Erro ao carregar posts salvos:', error);
    if (savedPostsContainer) {
      savedPostsContainer.innerHTML = '<p>Erro ao carregar posts salvos. Tente novamente.</p>';
    }
  }
}

window.unsavePost = async function unsavePost(postId) { // Corrigido: global para onclick
  try {
    const response = await fetch(`posts.php?post_id=${postId}`, { method: 'PATCH' });
    const data = await response.json();
    if (data.success) {
      loadSavedPosts();
    }
  } catch (err) {
    console.error('[Profile] Erro ao remover post salvo:', err);
  }
};

function setupProfileForm() {
  const profileForm = document.getElementById('profile-form');
  if (!profileForm) return;
  profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('email', document.getElementById('email').value);

    try {
      const response = await fetch('profile.php', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      if (data.success) {
        alert('Perfil atualizado com sucesso!');
      }
    } catch (error) {
      console.error('[Profile] Erro ao atualizar perfil:', error);
    }
  });
}

function setupLogoutButton() {
  const logoutButton = document.querySelector('.logout-button');
  if (!logoutButton) return;
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
    } catch (error) {
      console.error('[Profile] Erro ao fazer logout:', error);
    }
  });
}
