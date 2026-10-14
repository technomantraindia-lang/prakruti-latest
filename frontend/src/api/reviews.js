import { apiClient } from './client';

export const reviewsApi = {
  getTestimonials: async () => {
    return await apiClient('/testimonials');
  },

  addTestimonial: async (payload) => {
    let body;
    if (typeof FormData !== 'undefined' && payload instanceof FormData) {
      body = payload;
    } else {
      const { name, location, rating, review, image, video } = payload;
      const formData = new FormData();
      if (name) formData.append('name', name);
      if (location) formData.append('location', location);
      formData.append('rating', rating || 5);
      if (review) formData.append('review', review);
      if (image) formData.append('image', image);
      if (video) formData.append('video', video);
      body = formData;
    }

    return await apiClient('/testimonials', {
      method: 'POST',
      body,
    });
  },

  getProductReviews: async (productIdOrSlug) => {
    return await apiClient(`/products/${productIdOrSlug}/reviews`);
  },

  addProductReview: async (productIdOrSlug, { name, location, rating, comment }) => {
    return await apiClient(`/products/${productIdOrSlug}/reviews`, {
      method: 'POST',
      body: JSON.stringify({ name, location, rating, comment }),
    });
  },
};
