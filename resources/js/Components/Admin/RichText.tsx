import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import { useEffect } from 'react';

type Props = {
    value: string;
    onChange: (html: string) => void;
};

/**
 * The writing surface for text blocks.
 *
 * Deliberately small: paragraphs, bold, italic, lists, links. Headings are a
 * block pattern of their own, so they are not offered here - that is what
 * keeps the typographic rhythm of an entry intact.
 */
export default function RichText({ value, onChange }: Props) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: false,
                codeBlock: false,
                horizontalRule: false,
                blockquote: false,
            }),
            Link.configure({ openOnClick: false, autolink: true }),
        ],
        content: value,
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
        editorProps: {
            attributes: {
                class: 'prose-column max-w-none min-h-40 px-3 py-3 text-sm focus:outline-none',
            },
        },
    });

    // Keep the surface in step when the block is replaced underneath it,
    // without clobbering what is being typed right now.
    useEffect(() => {
        if (editor && !editor.isFocused && editor.getHTML() !== value) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [value, editor]);

    if (!editor) {
        return <div className="min-h-40 border border-hairline bg-paper" />;
    }

    const setLink = () => {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Adresse des Links', previous ?? 'https://');
        if (url === null) return;
        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    };

    return (
        <div className="border border-hairline bg-paper">
            <div className="hairline-b flex flex-wrap gap-px bg-hairline">
                <ToolbarButton active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()}>
                    Fett
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()}>
                    Kursiv
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()}>
                    Liste
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()}>
                    Nummeriert
                </ToolbarButton>
                <ToolbarButton active={editor.isActive('link')} onClick={setLink}>
                    Link
                </ToolbarButton>
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}

function ToolbarButton({
    children,
    onClick,
    active,
}: {
    children: string;
    onClick: () => void;
    active: boolean;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`label-xs min-h-9 px-3 ${active ? 'bg-ink text-paper' : 'bg-paper text-graphite hover:text-ink'}`}
        >
            {children}
        </button>
    );
}
