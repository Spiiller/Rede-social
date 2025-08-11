let imagesToRemove = [];

document.addEventListener('DOMContentLoaded', () => {
  loadAdminPosts();
  setupPostForm();
  setupEditForm();
});

async function loadAdminPosts() {
  try {
    const response = await fetch('admin_posts.php');
    if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
    const data = await response.json();

    const postsContainer = document.getElementById('admin-posts-container');
    postsContainer.innerHTML = '';

    if (data.success && data.posts && Array.isArray(data.posts)) {
      if (data.posts.length > 0) {
        data.posts.forEach(post => {
          // Visual minimalista: card clean, preview imagens
          const imagesHtml = post.images && post.images.length > 0
            ? post.images.map(img =>
                `<div class="preview-image-container">
                  <img src="${img}" alt="Imagem do post" class="preview-image" />
                </div>`
              ).join('')
            : '';
          const postElement = document.createElement('div');
          postElement.className = 'post';
          postElement.innerHTML = `
            <h3>${post.plantName}</h3>
            <p>${post.description}</p>
            <div class="images-preview">${imagesHtml}</div>
            <div style="margin-top: 0.5rem;">
              <button class="edit-btn" onclick="editPost(${post.id})">Editar</button>
              <button class="delete-btn" onclick="deletePost(${post.id})">Excluir</button>
            </div>
          `;
          postsContainer.appendChild(postElement);
        });
      } else {
        postsContainer.innerHTML = '<p>Nenhum post encontrado.</p>';
      }
    } else {
      throw new Error('Resposta inválida de admin_posts.php: ' + JSON.stringify(data));
    }
  } catch (error) {
    const postsContainer = document.getElementById('admin-posts-container');
    postsContainer.innerHTML = '<p>Erro ao carregar posts. Tente novamente.</p>';
  }
}

function setupPostForm() {
  const postForm = document.getElementById('post-form');
  const imageFilesInput = document.getElementById('imageFiles');
  const imagesPreview = document.getElementById('imagesPreview');

  imageFilesInput.addEventListener('change', () => {
    imagesPreview.innerHTML = '';
    Array.from(imageFilesInput.files).forEach(file => {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.className = 'preview-image';
      imagesPreview.appendChild(img);
    });
  });

  postForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('plantName', document.getElementById('plantName').value);
    formData.append('description', document.getElementById('description').value);
    Array.from(imageFilesInput.files).forEach(file => {
      formData.append('images[]', file);
    });

    try {
      const response = await fetch('admin_posts.php', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      if (data.success) {
        postForm.reset();
        imagesPreview.innerHTML = '';
        loadAdminPosts();
      } else {
        alert('Erro ao criar post: ' + data.message);
      }
    } catch (error) {
      alert('Erro ao enviar post: ' + error.message);
    }
  });
}

// Função global para usar nos botões dos posts
window.editPost = editPost;
window.deletePost = deletePost;

async function editPost(postId) {
  try {
    const response = await fetch(`admin_posts.php?post_id=${postId}`);
    const data = await response.json();

    if (data.success && data.post) {
      const editSection = document.getElementById('edit-post-section');
      const editForm = document.getElementById('edit-post-form');
      const editPlantName = document.getElementById('edit-plantName');
      const editDescription = document.getElementById('edit-description');
      const editImagesPreview = document.getElementById('edit-imagesPreview');
      const editPostId = document.getElementById('edit-post-id');

      editPostId.value = postId;
      editPlantName.value = data.post.plantName;
      editDescription.value = data.post.description;

      editImagesPreview.innerHTML = '';
      imagesToRemove = [];

      // Minimalista: preview + botão remover sobre a imagem
      if (data.post.images && data.post.images.length > 0) {
        data.post.images.forEach((imgSrc) => {
          const imgContainer = document.createElement('div');
          imgContainer.className = 'preview-image-container';
          imgContainer.innerHTML = `
            <img src="${imgSrc}" class="preview-image" alt="Imagem do post">
            <button class="remove-image-btn" type="button" data-imgsrc="${imgSrc}" title="Remover imagem">&#10006;</button>
          `;
          // Evento remover
          imgContainer.querySelector('.remove-image-btn').addEventListener('click', function () {
            const imgSrc = this.getAttribute('data-imgsrc');
            imagesToRemove.push(imgSrc);
            imgContainer.remove();
          });
          editImagesPreview.appendChild(imgContainer);
        });
      }

      editSection.classList.remove('hidden');
    } else {
      alert('Erro ao carregar post para edição: ' + data.message);
    }
  } catch (error) {
    alert('Erro ao carregar post para edição: ' + error.message);
  }
}

function setupEditForm() {
  const editForm = document.getElementById('edit-post-form');
  const imageFilesInput = document.getElementById('edit-imageFiles');
  const imagesPreview = document.getElementById('edit-imagesPreview');

  imageFilesInput.addEventListener('change', () => {
    // Preview das novas imagens com botão remover
    Array.from(imageFilesInput.files).forEach(file => {
      const imgContainer = document.createElement('div');
      imgContainer.className = 'preview-image-container';
      imgContainer.innerHTML = `
        <img src="${URL.createObjectURL(file)}" class="preview-image" alt="Nova imagem">
        <button class="remove-image-btn" type="button" title="Remover imagem">&#10006;</button>
      `;
      imgContainer.querySelector('.remove-image-btn').onclick = function () {
        imgContainer.remove();
      };
      imagesPreview.appendChild(imgContainer);
    });
  });

  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('post_id', document.getElementById('edit-post-id').value);
    formData.append('plantName', document.getElementById('edit-plantName').value);
    formData.append('description', document.getElementById('edit-description').value);
    formData.append('imagesToRemove', JSON.stringify(imagesToRemove));
    Array.from(imageFilesInput.files).forEach(file => {
      formData.append('images[]', file);
    });

    try {
      const response = await fetch('admin_posts.php', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      if (data.success) {
        editForm.reset();
        document.getElementById('edit-post-section').classList.add('hidden');
        loadAdminPosts();
      } else {
        alert('Erro ao editar post: ' + data.message);
      }
    } catch (error) {
      alert('Erro ao enviar edição: ' + error.message);
    }
  });
}

function cancelEdit() {
  document.getElementById('edit-post-form').reset();
  document.getElementById('edit-imagesPreview').innerHTML = '';
  document.getElementById('edit-post-section').classList.add('hidden');
}

async function deletePost(postId) {
  if (confirm('Tem certeza que deseja excluir este post?')) {
    try {
      const response = await fetch('admin_posts.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}`
      });
      const data = await response.json();
      if (data.success) {
        loadAdminPosts();
      } else {
        alert('Erro ao excluir post: ' + data.message);
      }
    } catch (error) {
      alert('Erro ao excluir post: ' + error.message);
    }
  }
}
