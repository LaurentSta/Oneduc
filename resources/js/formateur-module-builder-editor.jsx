import React, { useEffect, useRef, useState } from 'react';
import { creerSauvegardeLecon } from './sauvegarde-lecon';
import { createRoot } from 'react-dom/client';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';

const HEADING_LEVELS = [1, 2, 3, 4];

const BLOCK_LABELS = {
  text: 'Texte',
  image: 'Image',
  video: 'Vidéo',
  audio: 'Audio',
  quote: 'Citation',
  divider: 'Separateur',
  scorm: 'SCORM',
};

function TextBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" className="h-5 w-5">
      <line x1="4" y1="5" x2="16" y2="5" />
      <line x1="4" y1="10" x2="16" y2="10" />
      <line x1="4" y1="15" x2="11" y2="15" />
    </svg>
  );
}

function ImageBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <rect x="3" y="4" width="14" height="12" rx="1.5" />
      <circle cx="7.3" cy="8" r="1.1" fill="currentColor" stroke="none" />
      <path d="M4 14.5l3.8-4 3 3 2.7-3 2.5 3.5" />
    </svg>
  );
}

function VideoBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <rect x="3" y="4" width="14" height="12" rx="1.5" />
      <path d="M8.3 7.5v5l4.4-2.5z" fill="currentColor" stroke="none" />
    </svg>
  );
}

function QuoteBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="currentColor" stroke="none" className="h-5 w-5">
      <path d="M4.5 8c-1.4 0-2.5 1.1-2.5 2.6 0 1.4 1.1 2.4 2.4 2.4.2 0 .4 0 .6-.1-.3 1-1 1.8-1.9 2.3l.5.9C5.1 15.3 6 13.8 6 12v-1.4C6 9 5.6 8 4.5 8z" />
      <path d="M12.5 8c-1.4 0-2.5 1.1-2.5 2.6 0 1.4 1.1 2.4 2.4 2.4.2 0 .4 0 .6-.1-.3 1-1 1.8-1.9 2.3l.5.9c1.5-.8 2.4-2.3 2.4-4.1v-1.4C14 9 13.6 8 12.5 8z" />
    </svg>
  );
}

function AudioBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <path d="M4 12V8a1 1 0 011-1h2l4-3v12l-4-3H5a1 1 0 01-1-1z" />
      <path d="M14 7.5c.9.9.9 4.1 0 5" />
      <path d="M16.2 5.5c1.8 1.8 1.8 7.2 0 9" />
    </svg>
  );
}

function DividerBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" className="h-5 w-5">
      <line x1="3" y1="10" x2="7" y2="10" />
      <line x1="9.5" y1="10" x2="10.5" y2="10" />
      <line x1="13" y1="10" x2="17" y2="10" />
    </svg>
  );
}

function ScormBlockGlyph() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <path d="M10 2.5l6.5 3.3v8.4L10 17.5l-6.5-3.3V5.8z" />
      <path d="M3.5 5.8L10 9l6.5-3.2" />
      <path d="M10 9v8.5" />
    </svg>
  );
}

const BLOCK_GLYPHS = {
  text: TextBlockGlyph,
  image: ImageBlockGlyph,
  video: VideoBlockGlyph,
  audio: AudioBlockGlyph,
  quote: QuoteBlockGlyph,
  divider: DividerBlockGlyph,
  scorm: ScormBlockGlyph,
};

let blockIdSeq = 0;
function nextClientId() {
  blockIdSeq += 1;
  return `block-${Date.now()}-${blockIdSeq}`;
}

function generateContentBlockKey() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  let key = '';
  for (let i = 0; i < 32; i += 1) {
    key += Math.floor(Math.random() * 36).toString(36);
  }

  return key;
}

function createBlock(type) {
  switch (type) {
    case 'text':
      return { clientId: nextClientId(), type, html: '' };
    case 'image':
      return { clientId: nextClientId(), type, media_id: null, url: '', caption: '', alt: '', decorative: false };
    case 'video':
      return { clientId: nextClientId(), type, url: '', caption: '', transcript: '' };
    case 'audio':
      return { clientId: nextClientId(), type, media_id: null, url: '', caption: '', transcript: '' };
    case 'quote':
      return { clientId: nextClientId(), type, text: '', source: '' };
    case 'scorm':
      return { clientId: nextClientId(), type, content_block_key: generateContentBlockKey(), scorm_package_version_id: null, preview_url: '' };
    case 'divider':
    default:
      return { clientId: nextClientId(), type: 'divider', mode: 'simple' };
  }
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function classifyVideoUrl(url) {
  if (!url || !/^https?:\/\//i.test(url)) return null;

  const youtube = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{6,})/i);
  if (youtube) return { kind: 'youtube', embedUrl: `https://www.youtube.com/embed/${youtube[1]}` };

  const vimeo = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
  if (vimeo) return { kind: 'vimeo', embedUrl: `https://player.vimeo.com/video/${vimeo[1]}` };

  if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(url)) return { kind: 'file', embedUrl: url };

  return null;
}

function ToolbarButton({ title, active, disabled, onClick, children }) {
  return (
    <button
      type="button"
      title={title}
      aria-label={title}
      onClick={onClick}
      disabled={disabled}
      className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-gray-600 hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-30 ${active ? 'bg-bleuone/10 text-bleuone' : ''}`}
    >
      {children}
    </button>
  );
}

function ToolbarDivider() {
  return <span className="mx-1 h-6 w-px shrink-0 bg-gray-300" />;
}

function UndoIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <path d="M4 9h8a4 4 0 1 1 0 8h-2" />
      <path d="M4 9l4-4M4 9l4 4" />
    </svg>
  );
}

function RedoIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <path d="M16 9H8a4 4 0 1 0 0 8h2" />
      <path d="M16 9l-4-4M16 9l-4 4" />
    </svg>
  );
}

function LinkIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
      <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
      <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
    </svg>
  );
}

function BulletListIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" className="h-5 w-5">
      <circle cx="3" cy="5" r="1" fill="currentColor" stroke="none" />
      <circle cx="3" cy="10" r="1" fill="currentColor" stroke="none" />
      <circle cx="3" cy="15" r="1" fill="currentColor" stroke="none" />
      <line x1="7" y1="5" x2="17" y2="5" />
      <line x1="7" y1="10" x2="17" y2="10" />
      <line x1="7" y1="15" x2="17" y2="15" />
    </svg>
  );
}

function OrderedListIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" className="h-5 w-5">
      <text x="0.5" y="6.8" fontSize="5" fill="currentColor" stroke="none" fontFamily="sans-serif">1</text>
      <text x="0.5" y="11.8" fontSize="5" fill="currentColor" stroke="none" fontFamily="sans-serif">2</text>
      <text x="0.5" y="16.8" fontSize="5" fill="currentColor" stroke="none" fontFamily="sans-serif">3</text>
      <line x1="7" y1="5" x2="17" y2="5" />
      <line x1="7" y1="10" x2="17" y2="10" />
      <line x1="7" y1="15" x2="17" y2="15" />
    </svg>
  );
}

function TextBlockEditor({ block, onChange }) {
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        blockquote: false,
        link: { openOnClick: false },
      }),
    ],
    content: block.html,
    editorProps: {
      attributes: {
        class: 'rich-text-content min-h-[100px] rounded-b-[10px] border border-t-0 border-gray-300 px-3 py-2.5 text-sm focus:outline-none prose prose-sm max-w-none',
      },
    },
    onUpdate: ({ editor: currentEditor }) => {
      onChange({ ...block, html: currentEditor.getHTML() });
    },
  });

  const editorState = useEditorState({
    editor,
    selector: ({ editor: currentEditor }) => ({
      bold: currentEditor?.isActive('bold') ?? false,
      italic: currentEditor?.isActive('italic') ?? false,
      underline: currentEditor?.isActive('underline') ?? false,
      strike: currentEditor?.isActive('strike') ?? false,
      code: currentEditor?.isActive('code') ?? false,
      link: currentEditor?.isActive('link') ?? false,
      bulletList: currentEditor?.isActive('bulletList') ?? false,
      orderedList: currentEditor?.isActive('orderedList') ?? false,
      headingLevel: HEADING_LEVELS.find((level) => currentEditor?.isActive('heading', { level })) || 0,
      canUndo: currentEditor?.can().undo() ?? false,
      canRedo: currentEditor?.can().redo() ?? false,
    }),
  });

  const runCommand = (command) => () => {
    if (editor) command(editor.chain().focus());
  };

  const setHeading = (event) => {
    if (!editor) return;
    const level = Number(event.target.value);
    if (level === 0) {
      editor.chain().focus().setParagraph().run();
    } else {
      editor.chain().focus().toggleHeading({ level }).run();
    }
  };

  const setLink = () => {
    if (!editor) return;
    const previousUrl = editor.getAttributes('link').href || '';
    const url = window.prompt('Adresse du lien (laisser vide pour retirer) :', previousUrl);
    if (url === null) return;
    if (url === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
      return;
    }
    editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
  };

  return (
    <div>
      <div className="flex flex-wrap items-center gap-0.5 rounded-t-[10px] border border-gray-300 bg-gray-50 px-2 py-1.5">
        <ToolbarButton title="Annuler (Ctrl+Z)" disabled={!editorState.canUndo} onClick={runCommand((chain) => chain.undo().run())}>
          <UndoIcon />
        </ToolbarButton>
        <ToolbarButton title="Rétablir (Ctrl+Y)" disabled={!editorState.canRedo} onClick={runCommand((chain) => chain.redo().run())}>
          <RedoIcon />
        </ToolbarButton>

        <ToolbarDivider />

        <select
          value={editorState.headingLevel}
          onChange={setHeading}
          title="Style de paragraphe"
          className="h-10 rounded-md border-0 bg-transparent px-2 text-sm font-semibold text-gray-600 hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-bleuone/40"
        >
          <option value={0}>Normal</option>
          <option value={1}>Titre 1</option>
          <option value={2}>Titre 2</option>
          <option value={3}>Titre 3</option>
          <option value={4}>Titre 4</option>
        </select>

        <ToolbarDivider />

        <ToolbarButton title="Gras (Ctrl+B)" active={editorState.bold} onClick={runCommand((chain) => chain.toggleBold().run())}>
          <span className="text-lg font-bold">B</span>
        </ToolbarButton>
        <ToolbarButton title="Italique (Ctrl+I)" active={editorState.italic} onClick={runCommand((chain) => chain.toggleItalic().run())}>
          <span className="font-serif text-lg italic">I</span>
        </ToolbarButton>
        <ToolbarButton title="Souligné (Ctrl+U)" active={editorState.underline} onClick={runCommand((chain) => chain.toggleUnderline().run())}>
          <span className="text-lg underline">U</span>
        </ToolbarButton>
        <ToolbarButton title="Barré" active={editorState.strike} onClick={runCommand((chain) => chain.toggleStrike().run())}>
          <span className="text-lg line-through">S</span>
        </ToolbarButton>
        <ToolbarButton title="Code en ligne" active={editorState.code} onClick={runCommand((chain) => chain.toggleCode().run())}>
          <span className="font-mono text-sm">{'</>'}</span>
        </ToolbarButton>

        <ToolbarDivider />

        <ToolbarButton title="Liste à puces" active={editorState.bulletList} onClick={runCommand((chain) => chain.toggleBulletList().run())}>
          <BulletListIcon />
        </ToolbarButton>
        <ToolbarButton title="Liste numérotée" active={editorState.orderedList} onClick={runCommand((chain) => chain.toggleOrderedList().run())}>
          <OrderedListIcon />
        </ToolbarButton>

        <ToolbarDivider />

        <ToolbarButton title="Insérer un lien" active={editorState.link} onClick={setLink}>
          <LinkIcon />
        </ToolbarButton>
      </div>
      <EditorContent editor={editor} />
    </div>
  );
}

function ImageBlockEditor({ block, onChange, uploadUrl }) {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const fileInputRef = useRef(null);

  const handleFile = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setUploading(true);
    setError('');

    const formData = new FormData();
    formData.append('image', file);

    try {
      const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        body: formData,
      });

      if (!response.ok) throw new Error('upload failed');

      const data = await response.json();
      onChange({ ...block, media_id: data.media_id, url: data.url });
    } catch (e) {
      setError("Echec de l'envoi de l'image.");
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-2">
      {block.url ? (
        <img src={block.url} alt={block.decorative ? '' : (block.alt ?? block.caption ?? '')} className="max-h-48 rounded-lg border border-gray-200 object-contain" />
      ) : (
        <div className="flex h-24 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-400">
          Aucune image
        </div>
      )}

      <input
        ref={fileInputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif"
        onChange={handleFile}
        disabled={uploading}
        className="hidden"
      />
      <button
        type="button"
        onClick={() => fileInputRef.current?.click()}
        disabled={uploading}
        className="btn-oneduc disabled:cursor-not-allowed disabled:opacity-60"
      >
        {uploading ? 'Envoi en cours…' : block.url ? "Changer l'image" : 'Choisir une image'}
      </button>
      {error && <p className="text-xs text-red-500">{error}</p>}

      <input
        type="text"
        placeholder="Legende (optionnel)"
        value={block.caption || ''}
        onChange={(e) => onChange({ ...block, caption: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      />
      <label className="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" checked={Boolean(block.decorative)} onChange={(event) => onChange({ ...block, decorative: event.target.checked })} className="rounded border-gray-300 text-orangeone" />
        Cette image est uniquement décorative
      </label>
      {!block.decorative && <div>
        <label htmlFor={'alternative-' + block.clientId} className="block text-sm font-semibold text-bleuone">Description de l’image pour les lecteurs d’écran</label>
        <input id={'alternative-' + block.clientId} type="text" maxLength={512} value={block.alt ?? block.caption ?? ''}
               onChange={(event) => onChange({ ...block, alt: event.target.value })}
               placeholder="Décrivez l’information utile, sans répéter la légende."
               className="mt-2 w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm" />
      </div>}
    </div>
  );
}

function TranscriptEditor({ block, onChange }) {
  return <div>
    <label htmlFor={'transcription-' + block.clientId} className="block text-sm font-semibold text-bleuone">Transcription ou explication textuelle</label>
    <textarea id={'transcription-' + block.clientId} value={block.transcript || ''} maxLength={20000} rows={3}
              onChange={(event) => onChange({ ...block, transcript: event.target.value })}
              placeholder="Rendez les informations du média disponibles aussi en texte."
              className="mt-2 w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm" />
  </div>;
}

function VideoBlockEditor({ block, onChange, uploadUrl }) {
  const videoInfo = classifyVideoUrl(block.url || '');
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const fileInputRef = useRef(null);

  const handleFile = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setUploading(true);
    setError('');

    const formData = new FormData();
    formData.append('video', file);

    try {
      const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        body: formData,
      });

      if (!response.ok) throw new Error('upload failed');

      const data = await response.json();
      onChange({ ...block, url: data.url });
    } catch (e) {
      setError("Echec de l'envoi de la video.");
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-2">
      {videoInfo ? (
        videoInfo.kind === 'file' ? (
          <video src={videoInfo.embedUrl} controls className="w-full rounded-lg border border-gray-200" />
        ) : (
          <div className="aspect-video w-full overflow-hidden rounded-lg border border-gray-200">
            <iframe src={videoInfo.embedUrl} className="h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowFullScreen />
          </div>
        )
      ) : (
        <div className="flex h-24 items-center justify-center rounded-lg border border-dashed border-gray-300 text-center text-xs text-gray-400">
          {block.url ? 'URL non reconnue (YouTube, Vimeo ou fichier .mp4/.webm/.ogg)' : 'Aucune video'}
        </div>
      )}

      <input
        type="url"
        placeholder="Lien YouTube, Vimeo ou fichier video (.mp4)"
        value={block.url || ''}
        onChange={(e) => onChange({ ...block, url: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      />

      <div className="flex items-center gap-3">
        <span className="text-xs text-gray-400">ou</span>
        <input
          ref={fileInputRef}
          type="file"
          accept="video/mp4,video/webm,video/ogg"
          onChange={handleFile}
          disabled={uploading}
          className="hidden"
        />
        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={uploading}
          className="btn-oneduc disabled:cursor-not-allowed disabled:opacity-60"
        >
          {uploading ? 'Envoi en cours…' : 'Téléverser un fichier vidéo'}
        </button>
        <span className="text-xs text-gray-400">100 Mo max</span>
      </div>
      {error && <p className="text-xs text-red-500">{error}</p>}
      <TranscriptEditor block={block} onChange={onChange} />
      <input
        type="text"
        placeholder="Legende (optionnel)"
        value={block.caption || ''}
        onChange={(e) => onChange({ ...block, caption: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      />
    </div>
  );
}

function AudioBlockEditor({ block, onChange, uploadUrl, generateUrl }) {
  const [uploading, setUploading] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [error, setError] = useState('');
  const fileInputRef = useRef(null);

  const handleFile = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setUploading(true);
    setError('');

    const formData = new FormData();
    formData.append('audio', file);

    try {
      const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        body: formData,
      });

      if (!response.ok) throw new Error('upload failed');

      const data = await response.json();
      onChange({ ...block, media_id: data.media_id, url: data.url });
    } catch (e) {
      setError("Echec de l'envoi du fichier audio.");
    } finally {
      setUploading(false);
    }
  };

  const handleGenerate = async () => {
    setGenerating(true);
    setError('');

    try {
      const response = await fetch(generateUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
      });

      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'generation failed');

      onChange({ ...block, media_id: data.media_id, url: data.url });
    } catch (e) {
      setError(e.message && e.message !== 'generation failed' ? e.message : "Echec de la generation audio.");
    } finally {
      setGenerating(false);
    }
  };

  const busy = uploading || generating;

  return (
    <div className="space-y-2">
      {block.url ? (
        <audio controls className="w-full" src={block.url} />
      ) : (
        <div className="flex h-16 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-400">
          Aucun audio
        </div>
      )}

      <div className="flex flex-wrap items-center gap-3">
        <input
          ref={fileInputRef}
          type="file"
          accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/x-m4a"
          onChange={handleFile}
          disabled={busy}
          className="hidden"
        />
        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={busy}
          className="btn-oneduc disabled:cursor-not-allowed disabled:opacity-60"
        >
          {uploading ? 'Envoi en cours…' : block.url ? 'Changer le fichier' : 'Choisir un fichier audio'}
        </button>
        <span className="text-xs text-gray-400">ou</span>
        <button
          type="button"
          onClick={handleGenerate}
          disabled={busy}
          className="btn-oneduc-outline disabled:cursor-not-allowed disabled:opacity-60"
        >
          {generating ? 'Generation en cours…' : 'Generer via IA'}
        </button>
      </div>
      <p className="text-xs text-gray-400">La generation IA lit le texte de toute la lecon (blocs Texte et Citation).</p>
      {error && <p className="text-xs text-red-500">{error}</p>}
      <TranscriptEditor block={block} onChange={onChange} />

      <input
        type="text"
        placeholder="Legende (optionnel)"
        value={block.caption || ''}
        onChange={(e) => onChange({ ...block, caption: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      />
    </div>
  );
}

function ScormBlockEditor({ block, onChange, uploadUrl }) {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const fileInputRef = useRef(null);

  const handleFile = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setUploading(true);
    setError('');

    const formData = new FormData();
    formData.append('scorm', file);
    formData.append('content_block_key', block.content_block_key);

    try {
      const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        body: formData,
      });

      if (!response.ok) throw new Error('upload failed');

      const data = await response.json();
      onChange({ ...block, scorm_package_version_id: data.scorm_package_version_id, preview_url: data.preview_url });
    } catch (e) {
      setError("Echec de l'envoi du paquet SCORM.");
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-2">
      {block.preview_url ? (
        <iframe src={block.preview_url} title="Apercu SCORM" className="h-64 w-full rounded-lg border border-gray-200" />
      ) : (
        <div className="flex h-24 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-400">
          Aucun paquet SCORM
        </div>
      )}

      <input
        ref={fileInputRef}
        type="file"
        accept=".zip,application/zip"
        onChange={handleFile}
        disabled={uploading}
        className="hidden"
      />
      <button
        type="button"
        onClick={() => fileInputRef.current?.click()}
        disabled={uploading}
        className="btn-oneduc disabled:cursor-not-allowed disabled:opacity-60"
      >
        {uploading ? 'Envoi en cours…' : block.preview_url ? 'Remplacer le paquet SCORM' : 'Téléverser un paquet SCORM (.zip)'}
      </button>
      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  );
}

function QuoteBlockEditor({ block, onChange }) {
  return (
    <div className="space-y-2">
      <textarea
        rows={3}
        placeholder="Texte de la citation"
        value={block.text || ''}
        onChange={(e) => onChange({ ...block, text: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      />
      <input
        type="text"
        placeholder="Source / auteur (optionnel)"
        value={block.source || ''}
        onChange={(e) => onChange({ ...block, source: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      />
    </div>
  );
}

const DIVIDER_MODES = [
  { value: 'simple', label: 'Simple (ligne)' },
  { value: 'reveal', label: 'Voir la suite (révélation progressive)' },
];

function DividerBlockEditor({ block, onChange }) {
  const mode = block.mode || 'simple';
  return (
    <div className="space-y-2">
      <select
        value={mode}
        onChange={(e) => onChange({ ...block, mode: e.target.value })}
        className="w-full rounded-[10px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none"
      >
        {DIVIDER_MODES.map((m) => (
          <option key={m.value} value={m.value}>{m.label}</option>
        ))}
      </select>
      <hr className="border-gray-300" />
    </div>
  );
}

function BlockRow({ block, index, total, onMove, onChange, onRemove, onDragStart, onDragOver, onDrop, uploadUrl, videoUploadUrl, audioUploadUrl, audioGenerateUrl, scormUploadUrl }) {
  return (
    <div
      draggable
      onDragStart={() => onDragStart(index)}
      onDragOver={(e) => { e.preventDefault(); onDragOver(index); }}
      onDrop={() => onDrop(index)}
      className="rounded-[14px] border border-gray-200 bg-gray-50/60 p-3"
    >
      <div className="mb-2 flex items-center justify-between">
        <span className="cursor-move text-xs font-bold uppercase tracking-wide text-gray-400">
          ⠿ {BLOCK_LABELS[block.type] || block.type}
        </span>
        <div className="flex flex-wrap gap-3">
          <button type="button" onClick={() => onMove(index, -1)} disabled={index === 0} aria-label="Monter ce bloc" className="text-sm font-semibold text-bleuone disabled:opacity-30">↑</button>
          <button type="button" onClick={() => onMove(index, 1)} disabled={index === total - 1} aria-label="Descendre ce bloc" className="text-sm font-semibold text-bleuone disabled:opacity-30">↓</button>
          <button type="button" onClick={() => onRemove(index)} className="text-xs font-semibold text-red-700 hover:underline">Supprimer</button>
        </div>
      </div>

      {block.type === 'text' && <TextBlockEditor block={block} onChange={onChange} />}
      {block.type === 'image' && <ImageBlockEditor block={block} onChange={onChange} uploadUrl={uploadUrl} />}
      {block.type === 'video' && <VideoBlockEditor block={block} onChange={onChange} uploadUrl={videoUploadUrl} />}
      {block.type === 'audio' && <AudioBlockEditor block={block} onChange={onChange} uploadUrl={audioUploadUrl} generateUrl={audioGenerateUrl} />}
      {block.type === 'quote' && <QuoteBlockEditor block={block} onChange={onChange} />}
      {block.type === 'scorm' && <ScormBlockEditor block={block} onChange={onChange} uploadUrl={scormUploadUrl} />}
      {block.type === 'divider' && <DividerBlockEditor block={block} onChange={onChange} />}
    </div>
  );
}

const SAVE_STATUS = { IDLE: 'idle', UNSAVED: 'unsaved', SAVING: 'saving', SAVED: 'saved', ERROR: 'error' };

function SaveStatus({ status, savedAt, onSave }) {
  if (status === SAVE_STATUS.UNSAVED) {
    return <button type="button" onClick={onSave} className="text-xs font-semibold text-orangeone">Enregistrer maintenant</button>;
  }
  if (status === SAVE_STATUS.SAVING) {
    return <span className="text-xs text-gray-400">Enregistrement…</span>;
  }
  if (status === SAVE_STATUS.ERROR) {
    return <button type="button" onClick={onSave} className="text-xs font-semibold text-red-700">Échec de l’enregistrement — Réessayer</button>;
  }
  if (status === SAVE_STATUS.SAVED && savedAt) {
    return <span className="text-xs text-vertone">Enregistré à {savedAt}</span>;
  }
  return null;
}

function InsertBlockMenu({ index, onAdd, disabled }) {
  return (
    <details className="relative my-3">
      <summary className="cursor-pointer rounded-[10px] border border-dashed border-gray-300 px-3 py-2 text-center text-sm font-semibold text-bleuone">+ Ajouter un bloc ici</summary>
      <div className="mt-2 flex flex-wrap gap-2 rounded-[12px] border border-gray-200 bg-white p-3">
        {Object.entries(BLOCK_LABELS).map(([type, label]) => {
          const Glyph = BLOCK_GLYPHS[type];
          return <button key={type} type="button" disabled={disabled} onClick={(event) => {
            onAdd(type, index);
            event.currentTarget.closest('details').open = false;
          }} className="flex items-center gap-2 rounded-[8px] border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-600 hover:border-orangeone hover:text-orangeone disabled:opacity-40"><Glyph />{label}</button>;
        })}
      </div>
    </details>
  );
}

const LESSON_TEMPLATES = [
  { key: 'decouverte', label: 'Découvrir une notion', sections: [
    ['Une situation concrète', 'Présentez une situation familière au stagiaire et ce que cette leçon lui permettra de faire.'],
    ['Comprendre avec un exemple', 'Expliquez une idée à la fois, puis montrez un exemple concret.'],
    ['À vous de jouer', 'Proposez une courte activité pour mettre cette idée en pratique.'],
    ['À retenir', 'Résumez les points utiles et ajoutez une fiche mémo dans les ressources.'],
  ] },
  { key: 'demarche', label: 'Réaliser une démarche', sections: [
    ['Le résultat attendu', 'Décrivez la tâche à réaliser et les éléments à vérifier à la fin.'],
    ['Observer une démonstration', 'Ajoutez une démonstration ou des captures accompagnées d’une explication accessible.'],
    ['Réaliser les étapes', 'Décrivez les étapes et les aides disponibles en cas de difficulté.'],
    ['Vérifier son résultat', 'Proposez une tâche pratique et une liste des critères de réussite.'],
  ] },
  { key: 'entrainement', label: 'S’entraîner', sections: [
    ['Se rappeler l’essentiel', 'Rappelez brièvement ce qui sera utilisé dans l’exercice.'],
    ['Essayer', 'Décrivez un exercice réalisable avec les outils et les connaissances du stagiaire.'],
    ['Comprendre et recommencer', 'Expliquez les erreurs fréquentes et donnez une nouvelle occasion de s’entraîner.'],
    ['Vérifier les acquis', 'Choisissez une vérification adaptée à l’objectif de la leçon.'],
  ] },
];

function LectureEditor({ lectureId, initialTitle, initialBlocks, updateUrl, uploadUrl, videoUploadUrl, audioUploadUrl, audioGenerateUrl, scormUploadUrl }) {
  const [title, setTitle] = useState(initialTitle || '');
  const [blocks, setBlocks] = useState(() =>
    (initialBlocks || []).map((block) => ({ ...block, clientId: nextClientId() }))
  );
  const [status, setStatus] = useState(SAVE_STATUS.IDLE);
  const [savedAt, setSavedAt] = useState('');
  const [removedBlock, setRemovedBlock] = useState(null);
  const dragIndexRef = useRef(null);
  const skipNextSaveRef = useRef(true);
  const saveRef = useRef(null);
  if (!saveRef.current) {
    saveRef.current = creerSauvegardeLecon({
      notifier: (state) => {
        setStatus(state);
        if (state === SAVE_STATUS.SAVED) setSavedAt(new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }));
      },
      envoyer: async (payload) => {
        const response = await fetch(updateUrl, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
      });
      if (!response.ok) throw new Error('save failed');
      window.dispatchEvent(new CustomEvent('module-builder:lecture-saved', {
        detail: { id: lectureId, lecture_title: payload.lecture_title },
      }));
      },
    });
  }
  const save = () => saveRef.current.enregistrer().catch(() => {});

  useEffect(() => {
    if (skipNextSaveRef.current) {
      skipNextSaveRef.current = false;
      return undefined;
    }

    saveRef.current.planifier({
      lecture_title: title,
      content_blocks: JSON.stringify(blocks.map(({ clientId, ...rest }) => rest)),
    });
    return undefined;
  }, [title, blocks]);

  useEffect(() => {
    const manager = saveRef.current;
    let resubmitting = false;
    const beforeUnload = (event) => {
      if (!manager.estModifiee()) return;
      event.preventDefault();
      event.returnValue = '';
    };
    const followLink = async (event) => {
      const link = event.target.closest?.('a[href]');
      if (!link || !manager.estModifiee() || event.defaultPrevented || event.button !== 0
        || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.hasAttribute('download')) return;
      const url = new URL(link.href, window.location.href);
      if (url.origin !== window.location.origin || link.getAttribute('href').startsWith('#')) return;
      event.preventDefault();
      const popup = link.target === '_blank' ? window.open('about:blank', '_blank') : null;
      try {
        await manager.enregistrer();
        if (popup) { popup.opener = null; popup.location.href = url.href; }
        else window.location.assign(url.href);
      } catch { popup?.close(); }
    };
    const submitForm = async (event) => {
      if (resubmitting || !manager.estModifiee()) return;
      event.preventDefault();
      const form = event.target;
      const submitter = event.submitter;
      try {
        await manager.enregistrer();
        resubmitting = true;
        form.requestSubmit(submitter || undefined);
      } catch { /* Le statut visible permet de réessayer sans quitter la leçon. */ }
      finally { resubmitting = false; }
    };
    window.addEventListener('beforeunload', beforeUnload);
    document.addEventListener('click', followLink, true);
    document.addEventListener('submit', submitForm, true);
    return () => {
      manager.detruire();
      window.removeEventListener('beforeunload', beforeUnload);
      document.removeEventListener('click', followLink, true);
      document.removeEventListener('submit', submitForm, true);
    };
  }, []);

  useEffect(() => {
    const panel = document.querySelector('[data-import-support]');
    if (panel) panel.hidden = blocks.length > 0;
  }, [blocks.length]);

  const addBlock = (type, index) => setBlocks((prev) => {
    if (prev.length >= 100) return prev;
    const next = [...prev];
    next.splice(index, 0, createBlock(type));
    return next;
  });
  const updateBlock = (index, updated) => setBlocks((prev) => prev.map((b, i) => (i === index ? updated : b)));
  const removeBlock = (index) => {
    setRemovedBlock({ block: blocks[index], index });
    setBlocks((prev) => prev.filter((_, i) => i !== index));
  };
  const moveBlock = (index, direction) => {
    setBlocks((prev) => {
      const next = [...prev];
      const target = index + direction;
      if (target < 0 || target >= next.length) return prev;
      [next[index], next[target]] = [next[target], next[index]];
      return next;
    });
  };

  const handleDragStart = (index) => { dragIndexRef.current = index; };
  const handleDragOver = () => {};
  const handleDrop = (dropIndex) => {
    const dragIndex = dragIndexRef.current;
    dragIndexRef.current = null;
    if (dragIndex === null || dragIndex === dropIndex) return;

    setBlocks((prev) => {
      const next = [...prev];
      const [moved] = next.splice(dragIndex, 1);
      next.splice(dropIndex, 0, moved);
      return next;
    });
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          maxLength={255}
          placeholder="Titre de la leçon"
          className="flex-1 rounded-[10px] border border-gray-300 px-3 py-2 text-sm font-semibold focus:border-orangeone focus:outline-none"
        />
        <div aria-live="polite"><SaveStatus status={status} savedAt={savedAt} onSave={save} /></div>
      </div>

      {removedBlock && <div role="status" className="flex flex-wrap items-center justify-between gap-3 rounded-[10px] bg-gray-100 p-3 text-sm">
        <span>Bloc supprimé.</span>
        <button type="button" onClick={() => {
          setBlocks((prev) => {
            const next = [...prev];
            next.splice(Math.min(removedBlock.index, next.length), 0, removedBlock.block);
            return next;
          });
          setRemovedBlock(null);
        }} className="font-semibold text-bleuone">Annuler la suppression</button>
      </div>}

      {blocks.length === 0 && <div className="rounded-[14px] border border-gray-200 bg-gray-50 p-4">
        <p className="text-sm font-semibold text-bleuone">Commencer avec une trame pédagogique</p>
        <p className="mt-1 text-sm text-gray-600">Choisissez une structure, puis adaptez les textes et les activités à votre public.</p>
        <div className="mt-3 flex flex-wrap gap-2">{LESSON_TEMPLATES.map((template) => <button key={template.key} type="button" onClick={() => {
          const result = [];
          template.sections.forEach(([heading, body], index) => {
            if (index > 0) result.push({ ...createBlock('divider'), mode: 'reveal' });
            result.push({ ...createBlock('text'), html: '<h2>' + heading + '</h2><p>' + body + '</p>' });
          });
          setBlocks(result);
        }} className="rounded-[10px] border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-bleuone hover:border-orangeone">{template.label}</button>)}</div>
      </div>}

      <InsertBlockMenu index={0} onAdd={addBlock} disabled={blocks.length >= 100} />
      <div className="space-y-3">
        {blocks.map((block, index) => (
          <React.Fragment key={block.clientId}>
          <BlockRow
            block={block}
            index={index}
            total={blocks.length}
            onMove={moveBlock}
            onChange={(updated) => updateBlock(index, updated)}
            onRemove={removeBlock}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDrop={handleDrop}
            uploadUrl={uploadUrl}
            videoUploadUrl={videoUploadUrl}
            audioUploadUrl={audioUploadUrl}
            audioGenerateUrl={audioGenerateUrl}
            scormUploadUrl={scormUploadUrl}
          />
          <InsertBlockMenu index={index + 1} onAdd={addBlock} disabled={blocks.length >= 100} />
          </React.Fragment>
        ))}

        {blocks.length === 0 && (
          <p className="text-xs text-gray-400">Aucun bloc pour le moment. Ajoutez-en un ci-dessous.</p>
        )}
      </div>

    </div>
  );
}

export function mountModuleBuilderEditors() {
  document.querySelectorAll('[data-block-editor]').forEach((container) => {
    if (container.dataset.blockEditorMounted === '1') return;
    container.dataset.blockEditorMounted = '1';

    const lectureId = container.dataset.lectureId || '';
    const updateUrl = container.dataset.updateUrl || '';
    const uploadUrl = container.dataset.uploadUrl || '';
    const videoUploadUrl = container.dataset.videoUploadUrl || '';
    const audioUploadUrl = container.dataset.audioUploadUrl || '';
    const audioGenerateUrl = container.dataset.audioGenerateUrl || '';
    const scormUploadUrl = container.dataset.scormUploadUrl || '';
    const initialTitle = container.dataset.initialTitle || '';

    let initialBlocks = [];
    try {
      initialBlocks = JSON.parse(container.dataset.initialBlocks || '[]');
      if (!Array.isArray(initialBlocks)) initialBlocks = [];
    } catch (e) {
      initialBlocks = [];
    }

    createRoot(container).render(
      <LectureEditor
        lectureId={lectureId}
        initialTitle={initialTitle}
        initialBlocks={initialBlocks}
        updateUrl={updateUrl}
        uploadUrl={uploadUrl}
        videoUploadUrl={videoUploadUrl}
        audioUploadUrl={audioUploadUrl}
        audioGenerateUrl={audioGenerateUrl}
        scormUploadUrl={scormUploadUrl}
      />
    );
  });
}
