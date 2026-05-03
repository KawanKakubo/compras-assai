import sys
from docx import Document

def dump_docx(path):
    print(f"\n--- Dumping {path} ---")
    doc = Document(path)
    
    print("SIGNATURES CHECK:")
    for i, t in enumerate(doc.tables):
        text = "".join(c.text for r in t.rows for c in r.cells).upper()
        if "NOME:" in text:
            print(f"Table {i}: {text.replace('\n', ' ')[:100]}")

if __name__ == "__main__":
    import glob
    import os
    # Run generation first
    os.system("php scratch/test_env_fix.php")
    
    etp_files = sorted(glob.glob("/home/kawan/Documents/code/areas/SECTI/compras-assai/storage/app/public/ETP_*.docx"), key=os.path.getmtime)
    if etp_files: dump_docx(etp_files[-1])
