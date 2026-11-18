import { apiClient } from './client';

export const accountApi = {
  getProfile: async () => {
    return await apiClient('/me');
  },

  updateProfile: async (profileData) => {
    return await apiClient('/me', {
      method: 'PUT',
      body: JSON.stringify(profileData),
    });
  },

  getAddresses: async () => {
    return await apiClient('/addresses');
  },

  addAddress: async (addressData) => {
    return await apiClient('/addresses', {
      method: 'POST',
      body: JSON.stringify(addressData),
    });
  },

  getConsultations: async () => {
    return await apiClient('/customer/consultations');
  },

  trackConsultation: async (ref) => {
    return await apiClient(`/consultation-requests/track?ref=${encodeURIComponent(ref)}`);
  },

  getNotifications: async () => {
    return await apiClient('/customer/notifications');
  },

  markNotificationRead: async (id) => {
    return await apiClient(`/customer/notifications/${id}/read`, {
      method: 'POST',
    });
  },
};
