import React, { useEffect, useState } from 'react';
import './AccountPage.css';
import { accountApi, ordersApi } from '../../api';

const AccountPage = ({ currentUser, onLoginClick, onShopClick, onFamilyPackClick, onConsultationClick }) => {
  const [profile, setProfile] = useState(currentUser || null);
  const [orders, setOrders] = useState([]);
  const [consultations, setConsultations] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [unreadNotifCount, setUnreadNotifCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');

  // Edit details state
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState('');
  const [editPhone, setEditPhone] = useState('');
  const [updating, setUpdating] = useState(false);
  const [updateError, setUpdateError] = useState('');
  const [updateSuccess, setUpdateSuccess] = useState('');

  useEffect(() => {
    let mounted = true;

    const loadAccount = async () => {
      setLoading(true);
      setMessage('');

      const [profileRes, ordersRes, consultRes, notifRes] = await Promise.all([
        accountApi.getProfile(),
        ordersApi.getCustomerOrders(),
        accountApi.getConsultations ? accountApi.getConsultations().catch(() => ({ success: false, data: [] })) : Promise.resolve({ success: false, data: [] }),
        accountApi.getNotifications ? accountApi.getNotifications().catch(() => ({ success: false, data: [] })) : Promise.resolve({ success: false, data: [] }),
      ]);

      if (!mounted) return;

      if (profileRes.success) {
        setProfile(profileRes.data);
      } else if (profileRes.status === 401) {
        setMessage('Please login to view your account details.');
      } else {
        setMessage(profileRes.message || 'Could not load account details.');
      }

      if (ordersRes.success) {
        setOrders(Array.isArray(ordersRes.data) ? ordersRes.data : []);
      }

      if (consultRes && consultRes.success) {
        setConsultations(Array.isArray(consultRes.data) ? consultRes.data : []);
      }

      if (notifRes && notifRes.success) {
        setNotifications(Array.isArray(notifRes.data) ? notifRes.data : []);
        setUnreadNotifCount(notifRes.unread_count || 0);
      }

      setLoading(false);
    };

    loadAccount();

    return () => {
      mounted = false;
    };
  }, []);

  if (!currentUser && !profile) {
    return (
      <div className="account-page">
        <div className="container account-container">
          <div className="account-login-card">
            <span className="account-card-icon">👤</span>
            <h1>Login Required</h1>
            <p>Please login to view your profile, orders, and family pack details.</p>
            <button type="button" className="btn btn-primary" onClick={onLoginClick}>
              Login to Continue
            </button>
          </div>
        </div>
      </div>
    );
  }

  const displayUser = profile || currentUser || {};
  const totalSpent = orders.reduce((sum, order) => sum + Number(order.total || 0), 0);
  const familyPackOrders = orders.filter((order) => order.order_type === 'family_pack').length;
  const memberSince = displayUser.created_at
    ? new Date(displayUser.created_at).toLocaleDateString('en-IN', { month: 'short', year: 'numeric' })
    : 'New customer';
  const initials = String(displayUser.name || 'Customer')
    .split(' ')
    .map((part) => part.charAt(0))
    .join('')
    .slice(0, 2)
    .toUpperCase();

  const handleStartEdit = () => {
    setEditName(displayUser.name || '');
    setEditPhone(displayUser.phone || '');
    setUpdateError('');
    setUpdateSuccess('');
    setIsEditing(true);
  };

  const handleCancelEdit = () => {
    setIsEditing(false);
    setUpdateError('');
    setUpdateSuccess('');
  };

  const handleSaveProfile = async (e) => {
    e.preventDefault();
    if (!editName.trim()) {
      setUpdateError('Please enter your full name.');
      return;
    }

    setUpdating(true);
    setUpdateError('');
    setUpdateSuccess('');

    try {
      const res = await accountApi.updateProfile({
        name: editName.trim(),
        phone: editPhone.trim(),
      });

      if (res && res.success) {
        const updated = res.data || { ...displayUser, name: editName.trim(), phone: editPhone.trim() };
        setProfile(updated);

        try {
          const stored = localStorage.getItem('prakruti_customer_user');
          if (stored) {
            const parsed = JSON.parse(stored);
            localStorage.setItem('prakruti_customer_user', JSON.stringify({ ...parsed, ...updated }));
          }
        } catch (_) {}

        setUpdateSuccess('Profile details updated successfully!');
        setTimeout(() => {
          setIsEditing(false);
          setUpdateSuccess('');
        }, 1200);
      } else {
        setUpdateError(res?.message || 'Failed to update details. Please try again.');
      }
    } catch (err) {
      setUpdateError('An error occurred while updating profile.');
    } finally {
      setUpdating(false);
    }
  };

  const handleMarkAsRead = async (id) => {
    try {
      if (accountApi.markNotificationRead) {
        await accountApi.markNotificationRead(id);
      }
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n))
      );
      setUnreadNotifCount((prev) => Math.max(0, prev - 1));
    } catch (e) {
      console.error(e);
    }
  };

  return (
    <div className="account-page">
      <div className="container account-container">
        <div className="account-breadcrumbs">Home &nbsp; &gt; &nbsp; <span>My Account</span></div>

        <section className="account-hero-card">
          <div className="account-avatar">{initials || 'CU'}</div>
          <div className="account-hero-copy">
            <p className="account-kicker">Customer Account</p>
            <h1>{displayUser.name || 'My Account'}</h1>
            <p>{displayUser.email || 'Manage your Prakruti profile and purchases.'}</p>
          </div>
          <div className="account-hero-badge">
            <span>{displayUser.status || 'Active'}</span>
            <small>Member since {memberSince}</small>
          </div>
        </section>

        {message && <div className="account-alert">{message}</div>}

        <section className="account-stats-grid">
          <div className="account-stat-card">
            <span>Total Orders</span>
            <strong>{orders.length}</strong>
          </div>
          <div className="account-stat-card">
            <span>Family Packs</span>
            <strong>{familyPackOrders}</strong>
          </div>
          <div className="account-stat-card">
            <span>Total Spend</span>
            <strong>₹{totalSpent.toFixed(0)}</strong>
          </div>
        </section>

        <div className="account-grid">
          <section className="account-card">
            <div className="account-card-heading">
              <span>Profile Details</span>
              <div className="account-heading-actions">
                {loading && <small>Loading...</small>}
                {!isEditing ? (
                  <button
                    type="button"
                    className="btn-edit-details"
                    onClick={handleStartEdit}
                  >
                    ✏️ Edit Details
                  </button>
                ) : (
                  <span className="badge-editing">Editing</span>
                )}
              </div>
            </div>

            {isEditing ? (
              <form className="account-edit-form" onSubmit={handleSaveProfile}>
                <div className="edit-form-field">
                  <label htmlFor="edit_customer_name">Full Name *</label>
                  <input
                    id="edit_customer_name"
                    type="text"
                    value={editName}
                    onChange={(e) => setEditName(e.target.value)}
                    placeholder="Enter your name"
                    required
                  />
                </div>

                <div className="edit-form-field">
                  <label htmlFor="edit_customer_phone">Mobile Number</label>
                  <input
                    id="edit_customer_phone"
                    type="tel"
                    value={editPhone}
                    onChange={(e) => setEditPhone(e.target.value)}
                    placeholder="Enter 10-digit mobile number"
                  />
                </div>

                <div className="edit-form-field">
                  <label htmlFor="edit_customer_email">
                    Email Address <small>(Registered)</small>
                  </label>
                  <input
                    id="edit_customer_email"
                    type="email"
                    value={displayUser.email || ''}
                    disabled
                    className="input-disabled"
                  />
                </div>

                {updateError && <div className="edit-alert error">{updateError}</div>}
                {updateSuccess && <div className="edit-alert success">{updateSuccess}</div>}

                <div className="edit-form-buttons">
                  <button
                    type="submit"
                    className="btn-save-details"
                    disabled={updating}
                  >
                    {updating ? 'Saving...' : 'Save Changes'}
                  </button>
                  <button
                    type="button"
                    className="btn-cancel-edit"
                    onClick={handleCancelEdit}
                    disabled={updating}
                  >
                    Cancel
                  </button>
                </div>
              </form>
            ) : (
              <div className="account-detail-list">
                <div className="account-detail-row">
                  <span>Name</span>
                  <strong>{displayUser.name || '-'}</strong>
                </div>
                <div className="account-detail-row">
                  <span>Email</span>
                  <strong>{displayUser.email || '-'}</strong>
                </div>
                <div className="account-detail-row">
                  <span>Mobile</span>
                  <strong>{displayUser.phone || 'Not added'}</strong>
                </div>
                <div className="account-detail-row">
                  <span>Status</span>
                  <strong className="account-status">{displayUser.status || 'Active'}</strong>
                </div>
              </div>
            )}
          </section>

          <section className="account-card">
            <div className="account-card-heading">
              <span>Quick Actions</span>
            </div>

            <div className="account-actions">
              <button type="button" onClick={handleStartEdit}>
                <span>✏️ Edit Profile Details</span>
                <small>Update your name & phone</small>
              </button>
              <button type="button" onClick={onShopClick}>
                <span>Shop Products</span>
                <small>Browse organic staples</small>
              </button>
              <button type="button" onClick={onFamilyPackClick}>
                <span>Open Family Pack</span>
                <small>Reorder your saved pack</small>
              </button>
              {onConsultationClick && (
                <button type="button" onClick={onConsultationClick}>
                  <span>🩺 Doctor Consultation</span>
                  <small>Book or follow up on health</small>
                </button>
              )}
            </div>
          </section>
        </div>

        {/* Panel-to-Panel: Lifestyle Medicine Consultations Card */}
        <section className="account-card account-consultations-card">
          <div className="account-card-heading">
            <div className="d-flex align-items-center gap-2">
              <span>🩺 Lifestyle Medicine Video Consultations</span>
              <span className="badge-panel-sync">Panel-to-Panel Live</span>
            </div>
            <small>{consultations.length} session{consultations.length === 1 ? '' : 's'}</small>
          </div>

          {consultations.length > 0 ? (
            <div className="account-consultations-list">
              {consultations.map((item) => (
                <div className="account-consultation-row" key={item.id || item.reference_no}>
                  <div className="consult-row-header">
                    <div className="consult-info-left">
                      <div className="consult-ref-line">
                        <strong className="consult-ref-badge">#{item.reference_no}</strong>
                        <span className="consult-session-type">{item.session_type}</span>
                        {item.is_second_time || item.visit_number > 1 ? (
                          <span className="consult-visit-badge returning">
                            🔁 2nd Time Patient ({item.visit_badge_label || `Follow-up #${item.visit_number || 2}`})
                          </span>
                        ) : (
                          <span className="consult-visit-badge initial">
                            🌱 1st Time Patient (Initial)
                          </span>
                        )}
                        {item.admin_approved ? (
                          <span className="consult-status-pill approved">
                            <i className="status-dot"></i> Confirmed &amp; Approved
                          </span>
                        ) : (
                          <span className="consult-status-pill pending">
                            <i className="status-dot"></i> Pending Scheduling
                          </span>
                        )}
                      </div>
                      <div className="consult-concern-preview">
                        <strong>Focus / Concern:</strong> {item.concern || 'Therapeutic Lifestyle & Dietary Consultation'}
                      </div>
                      <div className="consult-doctor-line">
                        <span>👨‍⚕️ Specialist: <strong>{item.assigned_doctor || 'Dr. Varsha Shah'}</strong></span>
                        {item.created_at && <small className="text-muted ms-3">Booked on {item.created_at}</small>}
                      </div>
                    </div>

                    <div className="consult-action-right">
                      {item.scheduled_at ? (
                        <div className="consult-time-box">
                          <span className="time-kicker">Scheduled Appointment:</span>
                          <strong className="time-val">📅 {item.scheduled_at}</strong>
                        </div>
                      ) : (
                        <div className="consult-time-box waiting">
                          <span className="time-kicker">Appointment Time:</span>
                          <span className="time-val-muted">To be scheduled shortly</span>
                        </div>
                      )}

                      {/* Direct Panel-to-Panel Link */}
                      <div className="consult-btn-wrapper">
                        {item.admin_approved && item.meeting_link ? (
                          <a
                            href={item.meeting_link}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn-join-consultation-direct"
                            title="Join Video Consultation directly from your panel"
                          >
                            <span className="btn-icon">📹</span>
                            <span>Join Video Call</span>
                          </a>
                        ) : item.admin_approved ? (
                          <div className="consult-waiting-link-msg">
                            <span>🕒 Meeting link will be activated here before your slot</span>
                          </div>
                        ) : (
                          <div className="consult-waiting-approval-msg">
                            <span>⏳ Admin verifying payment &amp; scheduling slot</span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>

                  {item.doctor_notes && (
                    <div className="consult-patient-notes">
                      <strong>🩺 Doctor Advice &amp; Lifestyle Plan:</strong>
                      <p className="mb-0 mt-1">{item.doctor_notes}</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="account-empty-orders">
              <p>No doctor consultations scheduled yet. Book your personalized 1-on-1 session with our Lifestyle Medicine expert.</p>
              {onConsultationClick && (
                <button type="button" className="btn btn-primary" onClick={onConsultationClick}>
                  Book Doctor Consultation (₹1,500)
                </button>
              )}
            </div>
          )}
        </section>

        {/* Customer Alerts & Appointment Notifications */}
        <section className="account-card account-notifications-card">
          <div className="account-card-heading">
            <div className="d-flex align-items-center gap-2">
              <span>🔔 Consultation &amp; Account Alerts</span>
              {unreadNotifCount > 0 && (
                <span className="badge-notif-unread">{unreadNotifCount} New</span>
              )}
            </div>
            <small>{notifications.length} alert{notifications.length === 1 ? '' : 's'}</small>
          </div>

          {notifications.length > 0 ? (
            <div className="account-notifications-list">
              {notifications.map((notif) => {
                const data = notif.data || {};
                const isUnread = !notif.read_at;
                return (
                  <div
                    className={`account-notif-row ${isUnread ? 'unread' : 'read'}`}
                    key={notif.id}
                  >
                    <div className="notif-content-area">
                      <div className="notif-header-line">
                        <strong className="notif-title">{data.title || 'Notification'}</strong>
                        <span className="notif-time">{notif.created_at_human || notif.created_at}</span>
                      </div>
                      <p className="notif-message-text">{data.message}</p>
                      {data.metadata && data.metadata.zoom_link && (
                        <div className="notif-zoom-cta mt-2">
                          <a
                            href={data.metadata.zoom_link}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn btn-sm btn-zoom-notif"
                          >
                            📹 Open Zoom Meeting Link
                          </a>
                        </div>
                      )}
                    </div>
                    {isUnread && (
                      <button
                        type="button"
                        className="btn-mark-read"
                        onClick={() => handleMarkAsRead(notif.id)}
                        title="Mark as read"
                      >
                        ✓ Mark Read
                      </button>
                    )}
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="account-empty-notif">
              <p>No new notifications. You will receive real-time alerts when your consultation is booked, doctor assigned, and appointment time scheduled.</p>
            </div>
          )}
        </section>

        <section className="account-card account-orders-card">
          <div className="account-card-heading">
            <span>Recent Orders</span>
            <small>{orders.length} order{orders.length === 1 ? '' : 's'}</small>
          </div>

          {orders.length > 0 ? (
            <div className="account-orders-list">
              {orders.slice(0, 5).map((order) => (
                <div className="account-order-row" key={order.id || order.order_num || order.order_number}>
                  <div>
                    <strong>{order.order_num || order.order_number || `Order #${order.id}`}</strong>
                    <span>{order.order_type === 'family_pack' ? 'Family Pack' : 'Standard Order'}</span>
                  </div>
                  <div>
                    <strong>₹{Number(order.total || 0).toFixed(2)}</strong>
                    <span>{order.status || 'Pending'}</span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="account-empty-orders">
              <p>No orders found yet.</p>
              <button type="button" className="btn btn-primary" onClick={onShopClick}>
                Start Shopping
              </button>
            </div>
          )}
        </section>
      </div>
    </div>
  );
};

export default AccountPage;
