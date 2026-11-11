/** Texte brut → paragraphes. React échappe tout : aucun HTML saisi n'est interprété. */
export default function Prose({ text, className = '' }: { text: string | null | undefined; className?: string }) {
  if (!text) return null;

  return (
    <div className={`space-y-4 leading-relaxed ${className}`}>
      {text
        .split(/\n{2,}/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean)
        .map((paragraph, i) => (
          <p key={i} className="whitespace-pre-line">
            {paragraph}
          </p>
        ))}
    </div>
  );
}
