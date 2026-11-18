import { useEffect, useRef, useState } from 'react';
import { apiClient, getVisitorId } from '../../api/client';

const WebsiteVisitTracker = ({ page, currentUser }) => {
  const lastSentKey = useRef('');
  const [showContactPrompt, setShowContactPrompt] = useState(false);
  const [savingContact, setSavingContact] = useState(false);
  const [contactMessage, setContactMessage] = useState('');
  const [contact, setContact] = useState({
    name: currentUser?.name || '',
    email: currentUser?.email || '',
    phone: currentUser?.phone || '',
  });

  useEffect(() => {
    const pagePath = `${window.location.pathname}${window.location.hash.split('?')[0]}` || '/';
    const requestKey = `${page || 'home'}:${currentUser?.id || 'guest'}:${pagePath}`;
    if (lastSentKey.current === requestKey) return;
    lastSentKey.current = requestKey;

    apiClient('/website-visits', {
      method: 'POST',
      body: JSON.stringify({
        visitor_id: getVisitorId(),
        page: pagePath,
        page_title: document.title || 'Prakruti Heal',
      }),
    }).catch(() => {
      // Tracking must never interrupt shopping or navigation.
    });
  }, [page, currentUser?.id]);

  useEffect(() => {
    if (currentUser) {
      setContact((previous) => ({
        name: previous.name || currentUser.name || '',
        email: previous.email || currentUser.email || '',
        phone: previous.phone || currentUser.phone || '',
      }));
    }
  }, [currentUser]);

  useEffect(() => {
    if (localStorage.getItem('prakruti_contact_saved') === 'yes'
      || sessionStorage.getItem('prakruti_contact_prompt_dismissed') === 'yes') return undefined;

    const timer = window.setTimeout(() => setShowContactPrompt(true), 25000);
    return () => window.clearTimeout(timer);
  }, []);

  const dismissPrompt = () => {
    sessionStorage.setItem('prakruti_contact_prompt_dismissed', 'yes');
    setShowContactPrompt(false);
  };

  const saveContact = async (event) => {
    event.preventDefault();
    setSavingContact(true);
    setContactMessage('');

    const response = await apiClient('/website-visits', {
      method: 'POST',
      body: JSON.stringify({
        visitor_id: getVisitorId(),
        page: `${window.location.pathname}${window.location.hash.split('?')[0]}` || '/',
        page_title: document.title || 'Prakruti Heal',
        contact_submission: true,
        name: contact.name.trim(),
        email: contact.email.trim() || null,
        phone: contact.phone.trim() || null,
        marketing_consent: true,
        consent: true,
      }),
    });

    setSavingContact(false);
    if (!response.success) {
      const firstFieldError = Object.values(response.errors || {})[0]?.[0];
      setContactMessage(firstFieldError || response.message || 'We could not save your details. Please try again.');
      return;
    }

    localStorage.setItem('prakruti_contact_saved', 'yes');
    setShowContactPrompt(false);
  };

  if (!showContactPrompt) return null;

  return (
    <div role="presentation" onMouseDown={(event) => event.target === event.currentTarget && dismissPrompt()} style={{ position: 'fixed', inset: 0, zIndex: 12000, background: 'rgba(15, 35, 20, .55)', display: 'grid', placeItems: 'center', padding: 18 }}>
      <section role="dialog" aria-modal="true" aria-labelledby="visitor-contact-title" style={{ width: 'min(100%, 440px)', background: '#fffdf8', borderRadius: 18, padding: 26, boxShadow: '0 24px 80px rgba(0,0,0,.24)', position: 'relative' }}>
        <button type="button" onClick={dismissPrompt} aria-label="Close contact form" style={{ position: 'absolute', top: 12, right: 14, border: 0, background: 'transparent', fontSize: 24, color: '#53614f', cursor: 'pointer' }}>×</button>
        <div style={{ color: '#2d5a27', fontSize: 12, fontWeight: 800, letterSpacing: '.08em', textTransform: 'uppercase' }}>Prakruti Heal</div>
        <h2 id="visitor-contact-title" style={{ color: '#183d23', fontSize: 24, margin: '8px 0' }}>Stay in touch</h2>
        <p style={{ color: '#687365', fontSize: 14, lineHeight: 1.55, margin: '0 0 18px' }}>Share your details if you would like one helpful email from us when you leave without placing an order. Your email is needed to send it.</p>
        <form onSubmit={saveContact} style={{ display: 'grid', gap: 11 }}>
          <input required aria-label="Your name" placeholder="Your name" autoComplete="name" value={contact.name} onChange={(event) => setContact({ ...contact, name: event.target.value })} style={inputStyle} />
          <input required type="email" aria-label="Email address" placeholder="Email address *" autoComplete="email" value={contact.email} onChange={(event) => setContact({ ...contact, email: event.target.value })} style={inputStyle} />
          <input type="tel" aria-label="Mobile number" placeholder="Mobile number (optional)" autoComplete="tel" value={contact.phone} onChange={(event) => setContact({ ...contact, phone: event.target.value })} style={inputStyle} />
          <label style={{ display: 'flex', alignItems: 'flex-start', gap: 9, color: '#4f5d4b', fontSize: 12, lineHeight: 1.5 }}>
            <input type="checkbox" required style={{ marginTop: 2 }} />
            I agree that Prakruti Heal may use my contact details to send me one follow-up email if I do not place an order.
          </label>
          {contactMessage && <p role="alert" style={{ color: '#b42318', fontSize: 13, margin: 0 }}>{contactMessage}</p>}
          <button type="submit" disabled={savingContact} style={{ border: 0, borderRadius: 10, background: '#1f512b', color: 'white', padding: '12px 16px', fontWeight: 800, cursor: savingContact ? 'wait' : 'pointer' }}>
            {savingContact ? 'Saving…' : 'Send me one follow-up'}
          </button>
          <button type="button" onClick={dismissPrompt} style={{ border: 0, background: 'transparent', color: '#6d7769', padding: 5, cursor: 'pointer', fontSize: 13 }}>No thanks</button>
        </form>
      </section>
    </div>
  );
};

const inputStyle = {
  boxSizing: 'border-box',
  width: '100%',
  border: '1px solid #d7dfd3',
  borderRadius: 9,
  background: 'white',
  padding: '11px 12px',
  fontSize: 14,
  outlineColor: '#2d5a27',
};

export default WebsiteVisitTracker;
