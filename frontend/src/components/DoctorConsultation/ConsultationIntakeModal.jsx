import React, { useEffect, useMemo, useState } from 'react'

const STEP_LABELS = [
  'Personal details',
  'Goals & problems',
  'Medicines & history',
  'Lifestyle & meals',
  'Reports & review',
]

const blankMedication = () => ({
  medication: '',
  for_what: '',
  dosage: '',
  time: '',
  since: '',
  problems: '',
})

const createInitialIntake = (initialData = {}, currentUser = null) => {
  initialData = initialData || {}

  return ({
  consultation_date: initialData.consultation_date || '',
  name: initialData.name || currentUser?.name || '',
  gender: initialData.gender || '',
  address: initialData.address || '',
  phone: initialData.phone || currentUser?.phone || '',
  email: initialData.email || currentUser?.email || '',
  date_of_birth: initialData.date_of_birth || '',
  age: initialData.age || '',
  height: initialData.height || '',
  weight: initialData.weight || '',
  referred_by: initialData.referred_by || '',
  contact_method: initialData.contact_method || 'whatsapp',
  preferred_time: initialData.preferred_time || 'Evening (4 PM - 8 PM)',
  dietary_preference: initialData.dietary_preference || 'vegetarian',
  goals: initialData.goals || '',
  current_situation: Array.from({ length: 10 }, (_, index) => initialData.current_situation?.[index] || ''),
  medications: initialData.medications?.length ? initialData.medications : [blankMedication()],
  other_therapy: initialData.other_therapy || '',
  past_health_problems: initialData.past_health_problems || '',
  allergies: initialData.allergies || '',
  emergency_treatments: initialData.emergency_treatments || '',
  hospitalizations: initialData.hospitalizations || '',
  exercise: initialData.exercise || '',
  sleep_quality: initialData.sleep_quality || '',
  sleep_difficulty: initialData.sleep_difficulty || '',
  bedtime: initialData.bedtime || '',
  wake_time: initialData.wake_time || '',
  major_stress: initialData.major_stress || '',
  stress_details: initialData.stress_details || '',
  habits: initialData.habits || '',
  family_history: initialData.family_history || '',
  daily_meal_plan: {
    wake_up: initialData.daily_meal_plan?.wake_up || '',
    breakfast: initialData.daily_meal_plan?.breakfast || '',
    snack: initialData.daily_meal_plan?.snack || '',
    lunch: initialData.daily_meal_plan?.lunch || '',
    dinner: initialData.daily_meal_plan?.dinner || '',
    bedtime: initialData.daily_meal_plan?.bedtime || '',
    anything_in_between: initialData.daily_meal_plan?.anything_in_between || '',
  },
  consent: initialData.consent !== false,
  })
}

const Field = ({ label, hint, children, full = false }) => (
  <div className={`intake-field${full ? ' intake-field-full' : ''}`}>
    <label>{label}{hint && <span className="intake-field-hint">{hint}</span>}</label>
    {children}
  </div>
)

const TextAreaField = ({ label, value, onChange, placeholder, rows = 4, full = true }) => (
  <Field label={label} full={full}>
    <textarea value={value} onChange={(event) => onChange(event.target.value)} placeholder={placeholder} rows={rows} />
  </Field>
)

export default function ConsultationIntakeModal({ isOpen, onClose, onComplete, currentUser, initialData }) {
  const [step, setStep] = useState(1)
  const [form, setForm] = useState(() => createInitialIntake(initialData, currentUser))
  const [labReports, setLabReports] = useState([])
  const [error, setError] = useState('')

  useEffect(() => {
    if (!isOpen) return undefined
    const handleEscape = (event) => {
      if (event.key === 'Escape') onClose()
    }
    document.addEventListener('keydown', handleEscape)
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', handleEscape)
      document.body.style.overflow = ''
    }
  }, [isOpen, onClose])

  const setValue = (field, value) => {
    setForm((previous) => ({ ...previous, [field]: value }))
    setError('')
  }

  const setMeal = (field, value) => {
    setForm((previous) => ({ ...previous, daily_meal_plan: { ...previous.daily_meal_plan, [field]: value } }))
    setError('')
  }

  const setProblem = (index, value) => {
    setForm((previous) => {
      const current = [...previous.current_situation]
      current[index] = value
      return { ...previous, current_situation: current }
    })
    setError('')
  }

  const setMedication = (index, field, value) => {
    setForm((previous) => {
      const medications = previous.medications.map((item, itemIndex) => itemIndex === index ? { ...item, [field]: value } : item)
      return { ...previous, medications }
    })
    setError('')
  }

  const addMedication = () => setForm((previous) => ({ ...previous, medications: [...previous.medications, blankMedication()] }))

  const removeMedication = (index) => setForm((previous) => ({
    ...previous,
    medications: previous.medications.length === 1 ? [blankMedication()] : previous.medications.filter((_, itemIndex) => itemIndex !== index),
  }))

  const validateStep = () => {
    if (step === 1) {
      if (!form.name.trim()) return 'Please enter your full name.'
      if (!form.phone.trim() && !form.email.trim()) return 'Please provide a phone number or email address.'
    }
    if (step === 2 && !form.goals.trim() && !form.current_situation.some((item) => item.trim())) {
      return 'Please tell us about your goals or current health concerns.'
    }
    if (step === 5 && !form.consent) return 'Please accept the consent and information declaration to continue.'
    return ''
  }

  const nextStep = () => {
    const validationError = validateStep()
    if (validationError) {
      setError(validationError)
      return
    }
    setError('')
    setStep((previous) => Math.min(previous + 1, STEP_LABELS.length))
  }

  const previousStep = () => {
    setError('')
    setStep((previous) => Math.max(previous - 1, 1))
  }

  const submitIntake = (event) => {
    event.preventDefault()
    const validationError = validateStep()
    if (validationError) {
      setError(validationError)
      return
    }
    onComplete({
      intakeData: {
        ...form,
        current_situation: form.current_situation.filter((item) => item.trim()),
        medications: form.medications.filter((item) => Object.values(item).some((value) => String(value).trim())),
      },
      labReports,
    })
  }

  const problemCount = useMemo(() => form.current_situation.filter((item) => item.trim()).length, [form.current_situation])

  if (!isOpen) return null

  return (
    <div className="intake-modal-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && onClose()}>
      <div className="intake-modal" role="dialog" aria-modal="true" aria-labelledby="intake-modal-title">
        <div className="intake-modal-header">
          <div>
            <span className="intake-eyebrow">PRIVATE HEALTH INTAKE</span>
            <h2 id="intake-modal-title">Your Lifestyle Medicine Consultation</h2>
            <p>Answer one section at a time. Your answers help the expert prepare before speaking with you.</p>
          </div>
          <button type="button" className="intake-close" onClick={onClose} aria-label="Close consultation form">×</button>
        </div>

        <div className="intake-progress" aria-label="Consultation form progress">
          {STEP_LABELS.map((label, index) => {
            const number = index + 1
            return (
              <div key={label} className={`intake-progress-step${number === step ? ' active' : ''}${number < step ? ' complete' : ''}`}>
                <span>{number < step ? '✓' : number}</span>
                <small>{label}</small>
              </div>
            )
          })}
        </div>

        <form className="intake-form" onSubmit={submitIntake}>
          <div className="intake-form-scroll">
          {step === 1 && (
            <div className="intake-step-content">
              <div className="intake-step-heading"><span>01</span><div><h3>Let’s start with your details</h3><p>Tell us how we can identify and reach you.</p></div></div>
              <div className="intake-grid">
                <Field label="Consultation date"><input type="date" value={form.consultation_date} onChange={(event) => setValue('consultation_date', event.target.value)} /></Field>
                <Field label="Full name *"><input autoFocus type="text" value={form.name} onChange={(event) => setValue('name', event.target.value)} placeholder="Your full name" /></Field>
                <Field label="Gender"><select value={form.gender} onChange={(event) => setValue('gender', event.target.value)}><option value="">Select</option><option>Female</option><option>Male</option><option>Non-binary</option><option>Prefer not to say</option></select></Field>
                <Field label="Phone number *"><input type="tel" value={form.phone} onChange={(event) => setValue('phone', event.target.value)} placeholder="WhatsApp or mobile number" /></Field>
                <Field label="Email address"><input type="email" value={form.email} onChange={(event) => setValue('email', event.target.value)} placeholder="name@example.com" /></Field>
                <Field label="Date of birth"><input type="date" value={form.date_of_birth} onChange={(event) => setValue('date_of_birth', event.target.value)} /></Field>
                <Field label="Age"><input type="number" min="0" max="120" value={form.age} onChange={(event) => setValue('age', event.target.value)} placeholder="Years" /></Field>
                <Field label="Height"><input type="text" value={form.height} onChange={(event) => setValue('height', event.target.value)} placeholder="e.g. 170 cm" /></Field>
                <Field label="Weight"><input type="text" value={form.weight} onChange={(event) => setValue('weight', event.target.value)} placeholder="e.g. 68 kg" /></Field>
                <Field label="Referred by"><input type="text" value={form.referred_by} onChange={(event) => setValue('referred_by', event.target.value)} placeholder="Name or source" /></Field>
                <Field label="Preferred contact"><select value={form.contact_method} onChange={(event) => setValue('contact_method', event.target.value)}><option value="whatsapp">WhatsApp</option><option value="phone">Phone call</option><option value="email">Email</option></select></Field>
                <Field label="Preferred time"><select value={form.preferred_time} onChange={(event) => setValue('preferred_time', event.target.value)}><option>Morning (9 AM - 12 PM)</option><option>Afternoon (12 PM - 4 PM)</option><option>Evening (4 PM - 8 PM)</option><option>Weekend Slot</option></select></Field>
                <Field label="Current diet"><select value={form.dietary_preference} onChange={(event) => setValue('dietary_preference', event.target.value)}><option value="vegetarian">Vegetarian</option><option value="vegan">Vegan / plant based</option><option value="jain">Jain</option><option value="mixed">Mixed diet</option></select></Field>
                <Field label="Address" full><textarea rows="3" value={form.address} onChange={(event) => setValue('address', event.target.value)} placeholder="Your current address" /></Field>
              </div>
            </div>
          )}

          {step === 2 && (
            <div className="intake-step-content">
              <div className="intake-step-heading"><span>02</span><div><h3>What would you like to change?</h3><p>Start with your goals, then list each current problem from oldest to latest.</p></div></div>
              <div className="intake-grid">
                <TextAreaField label="Your goals" value={form.goals} onChange={(value) => setValue('goals', value)} placeholder="What do you hope to achieve from this consultation?" rows={4} />
                <Field label={`Current problems (${problemCount}/10)`} full>
                  <div className="intake-problem-list">
                    {form.current_situation.map((problem, index) => <div className="intake-numbered-row" key={index}><span>{index + 1}.</span><textarea rows="2" value={problem} onChange={(event) => setProblem(index, event.target.value)} placeholder="Problem, symptoms, duration, how it began, and anything relevant" /></div>)}
                  </div>
                </Field>
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="intake-step-content">
              <div className="intake-step-heading"><span>03</span><div><h3>Medicines and past history</h3><p>Please include supplements, topical applications, and oral contraceptives.</p></div></div>
              <div className="intake-medication-table-wrap">
                <div className="intake-medication-table-head"><span>Medication / supplement</span><span>For what</span><span>Dosage</span><span>Time</span><span>Since</span><span>Problems?</span><span></span></div>
                {form.medications.map((medication, index) => <div className="intake-medication-row" key={index}>
                  {Object.keys(blankMedication()).map((field) => <input key={field} aria-label={field.replace('_', ' ')} value={medication[field]} onChange={(event) => setMedication(index, field, event.target.value)} placeholder={field === 'medication' ? 'Medicine / supplement' : field === 'for_what' ? 'Reason' : field === 'dosage' ? 'Dose' : field === 'time' ? 'Time' : field === 'since' ? 'Duration' : 'Any issue'} />)}
                  <button type="button" className="intake-remove-row" onClick={() => removeMedication(index)} aria-label="Remove medication">×</button>
                </div>)}
                <button type="button" className="intake-add-row" onClick={addMedication}>+ Add another medication</button>
              </div>
              <div className="intake-grid">
                <TextAreaField label="Other therapy or treatment besides medicines" value={form.other_therapy} onChange={(value) => setValue('other_therapy', value)} placeholder="Include ongoing therapies or treatments" rows={3} />
                <TextAreaField label="Major past health problems or surgeries" value={form.past_health_problems} onChange={(value) => setValue('past_health_problems', value)} rows={3} />
                <TextAreaField label="Major allergies or food aggravations" value={form.allergies} onChange={(value) => setValue('allergies', value)} rows={3} />
                <TextAreaField label="Emergency treatments needed in the past" value={form.emergency_treatments} onChange={(value) => setValue('emergency_treatments', value)} rows={3} />
                <TextAreaField label="Past hospitalizations and condition" value={form.hospitalizations} onChange={(value) => setValue('hospitalizations', value)} rows={3} />
              </div>
            </div>
          )}

          {step === 4 && (
            <div className="intake-step-content">
              <div className="intake-step-heading"><span>04</span><div><h3>Your daily lifestyle</h3><p>This gives the expert a realistic picture of your routine.</p></div></div>
              <div className="intake-grid">
                <TextAreaField label="Exercise" value={form.exercise} onChange={(value) => setValue('exercise', value)} placeholder="Types of exercise and approximate time devoted to each" rows={3} />
                <TextAreaField label="How is your sleep?" value={form.sleep_quality} onChange={(value) => setValue('sleep_quality', value)} placeholder="How well do you sleep?" rows={3} />
                <TextAreaField label="Any sleep difficulty?" value={form.sleep_difficulty} onChange={(value) => setValue('sleep_difficulty', value)} rows={3} />
                <Field label="What time do you go to bed?"><input type="time" value={form.bedtime} onChange={(event) => setValue('bedtime', event.target.value)} /></Field>
                <Field label="What time do you wake up?"><input type="time" value={form.wake_time} onChange={(event) => setValue('wake_time', event.target.value)} /></Field>
                <Field label="Do you have major stress?"><select value={form.major_stress} onChange={(event) => setValue('major_stress', event.target.value)}><option value="">Select</option><option>Yes</option><option>No</option><option>Sometimes</option></select></Field>
                <TextAreaField label="Tell us more about your stress" value={form.stress_details} onChange={(value) => setValue('stress_details', value)} rows={3} />
                <TextAreaField label="Major habits or addictions" value={form.habits} onChange={(value) => setValue('habits', value)} placeholder="Tea, coffee, smoking, alcohol, drugs, or anything else" rows={3} />
                <TextAreaField label="Family history" value={form.family_history} onChange={(value) => setValue('family_history', value)} placeholder="Any family history of major conditions? Please describe." rows={3} />
              </div>

              <div className="intake-meal-plan">
                <div className="intake-subheading"><h4>Daily meal plan</h4><p>Please include all foods, teas, coffees, snacks, and options you normally take.</p></div>
                <div className="intake-grid">
                  {[['wake_up', 'Wake up'], ['breakfast', 'Breakfast'], ['snack', 'Snack'], ['lunch', 'Lunch'], ['dinner', 'Dinner'], ['bedtime', 'Bedtime'], ['anything_in_between', 'Anything in between']].map(([field, label]) => <Field key={field} label={`${label} —`}><input type="text" value={form.daily_meal_plan[field]} onChange={(event) => setMeal(field, event.target.value)} placeholder="List foods and drinks" /></Field>)}
                </div>
              </div>
            </div>
          )}

          {step === 5 && (
            <div className="intake-step-content">
              <div className="intake-step-heading"><span>05</span><div><h3>Reports, consent and review</h3><p>Share your recent laboratory tests if available. The care team will review the information before your session.</p></div></div>
              <Field label="Upload your latest laboratory tests" hint="PDF, JPG, PNG, WEBP, DOC or DOCX · up to 15 MB each" full>
                <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" onChange={(event) => setLabReports(Array.from(event.target.files || []))} />
                {labReports.length > 0 && <div className="intake-file-list">{labReports.map((file) => <span key={`${file.name}-${file.size}`}>{file.name}</span>)}</div>}
              </Field>
              <div className="intake-review-card">
                <strong>Before you continue</strong>
                <span>{form.name || 'Your name'} · {form.phone || form.email || 'Contact not added'}</span>
                <span>{problemCount} current problem{problemCount === 1 ? '' : 's'} · {form.medications.filter((item) => item.medication.trim()).length} medication entr{form.medications.filter((item) => item.medication.trim()).length === 1 ? 'y' : 'ies'}</span>
                <span>Consultation date: {form.consultation_date || 'To be scheduled by the care team'}</span>
              </div>
              <label className="intake-consent-row"><input type="checkbox" checked={form.consent} onChange={(event) => setValue('consent', event.target.checked)} /><span>I confirm that the information I have provided is accurate to the best of my knowledge. I consent to share it with Prakruti’s Lifestyle Medicine Expert for consultation.</span></label>
              <p className="intake-disclaimer">Doctors and nutritionists shall not be held responsible for consequences arising from wrong or incomplete information supplied by the customer.</p>
            </div>
          )}

          {error && <div className="intake-error" role="alert">{error}</div>}
          </div>
          <div className="intake-actions">
            {step > 1 ? <button type="button" className="intake-secondary-btn" onClick={previousStep}>← Back</button> : <button type="button" className="intake-secondary-btn" onClick={onClose}>Cancel</button>}
            {step < STEP_LABELS.length ? <button type="button" className="intake-primary-btn" onClick={nextStep}>Continue <span>→</span></button> : <button type="submit" className="intake-primary-btn">Review &amp; continue to payment <span>→</span></button>}
          </div>
        </form>
      </div>
    </div>
  )
}
