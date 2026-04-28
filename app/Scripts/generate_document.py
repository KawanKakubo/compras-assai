import sys
import json
import argparse
from docx import Document
from docx.shared import Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

def set_run_text(p, text):
    """Sets the text of a paragraph, handling newlines and preserving first run formatting."""
    if not p.runs:
        p.add_run("")
    
    first_run = p.runs[0]
    lines = str(text if text is not None else "").split("\n")
    first_run.text = lines[0]
    for line in lines[1:]:
        first_run.add_break()
        first_run.add_text(line)
    
    # Clear other runs
    for i in range(1, len(p.runs)):
        p.runs[i].text = ""

def handle_checkboxes(p, value):
    """Handles ( ) Sim ( ) Não checkboxes in a paragraph."""
    text = p.text
    if "( ) Sim" in text or "( ) Não" in text:
        if value == "Sim":
            text = text.replace("( ) Sim", "(X) Sim").replace("(X) Não", "( ) Não")
        elif value == "Não":
            text = text.replace("( ) Não", "(X) Não").replace("(X) Sim", "( ) Sim")
        
        # Preserve formatting by updating runs
        for run in p.runs:
            if "( ) Sim" in run.text:
                run.text = run.text.replace("( ) Sim", "(X) Sim" if value == "Sim" else "( ) Sim")
            if "( ) Não" in run.text:
                run.text = run.text.replace("( ) Não", "(X) Não" if value == "Não" else "( ) Não")

def replace_placeholders(doc, data):
    for p in doc.paragraphs:
        full_text = "".join(run.text for run in p.runs)
        if not full_text.strip(): continue
        
        original_text = full_text
        for key, value in data.items():
            if key in full_text:
                full_text = full_text.replace(key, str(value if value is not None else ""))
        
        if full_text != original_text:
            set_run_text(p, full_text)

    # Table placeholders
    for table in doc.tables:
        for row in table.rows:
            for cell in row.cells:
                # Handle checkboxes in tables too
                for p in cell.paragraphs:
                    full_text = "".join(run.text for run in p.runs)
                    for key, value in data.items():
                        if key in full_text:
                            if value in ["Sim", "Não"]:
                                handle_checkboxes(p, value)
                            else:
                                full_text = full_text.replace(key, str(value))
                    
                    if full_text != "".join(run.text for run in p.runs):
                        set_run_text(p, full_text)

def process_signatures(doc, data):
    for table in doc.tables:
        all_text = "".join(cell.text for row in table.rows for cell in row.cells).upper()
        if not any(x in all_text for x in ["AUTOR DO DFD", "RESPONSÁVEL", "IDENTIFICAÇÃO"]):
            continue
            
        for row in table.rows:
            for cell in row.cells:
                cell_text = cell.text.upper()
                is_secretary = any(x in cell_text for x in ["SECRETARIA", "SECRETARIO", "RESPONSÁVEL"])
                suffix = "secretario" if is_secretary else "autor"
                
                for p in cell.paragraphs:
                    txt = p.text.strip().upper()
                    for label in ["NOME:", "CPF:", "CARGO/FUNÇÃO:", "CARGO:"]:
                        if txt.startswith(label):
                            # Avoid double filling
                            if len(txt) <= len(label) + 1:
                                key = f"{label.split(':')[0]}:_{suffix}"
                                val = data.get(key, data.get(label))
                                set_run_text(p, label + " " + str(val if val is not None else ""))

def process_instruction_paragraphs(doc, instruction_map):
    for i, p in enumerate(doc.paragraphs):
        text = p.text.strip()
        # Look for the heading in THIS paragraph or the PREVIOUS one
        for needle, replacement in instruction_map.items():
            if needle.lower() in text.lower():
                # If this paragraph IS the heading (short, or has numbers), target the NEXT one
                target_p = p
                if (len(text) < 100 or text[0].isdigit()) and i + 1 < len(doc.paragraphs):
                    next_p = doc.paragraphs[i+1]
                    # Only target if next is empty, red, or has checkboxes
                    next_text = next_p.text.strip()
                    if not next_text or "(" in next_text:
                        target_p = next_p
                
                if replacement in ["Sim", "Não"]:
                    handle_checkboxes(target_p, replacement)
                else:
                    set_run_text(target_p, replacement)
                break

def remove_red_text(doc):
    for p in doc.paragraphs:
        for run in p.runs:
            if run.font.color and run.font.color.rgb:
                if str(run.font.color.rgb).upper() in ['FF0000', 'EE0000', 'FF3333']:
                    run.text = ""

def create_items_table(doc, placeholder, items, headers=None, total_label="TOTAL:"):
    if not items: return False
    if headers is None: headers = ['Item', 'Descrição', 'Qtd', 'Unid', 'V. Unit', 'V. Total']
    
    for p in doc.paragraphs:
        if placeholder in p.text or (placeholder == "Estimativa da Demanda" and p.text.strip() == placeholder):
            if hasattr(p, "_table_inserted"): continue
            
            table = doc.add_table(rows=1, cols=len(headers))
            table.style = 'Table Grid'
            hdr_cells = table.rows[0].cells
            for i, h in enumerate(headers):
                hdr_cells[i].text = h
                for pr in hdr_cells[i].paragraphs:
                    for run in pr.runs: run.font.bold = True
            
            total_geral = 0
            for i, item in enumerate(items):
                qty = float(item.get('quantity', 0))
                val = float(item.get('unit_value', 0))
                total = qty * val
                total_geral += total
                row_cells = table.add_row().cells
                row_cells[0].text = str(i + 1)
                row_cells[1].text = item.get('description', '')
                row_cells[2].text = f"{qty:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                row_cells[3].text = item.get('unit', '')
                row_cells[4].text = f"R$ {val:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                row_cells[5].text = f"R$ {total:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
            
            row_cells = table.add_row().cells
            row_cells[0].text = total_label
            row_cells[0].merge(row_cells[4])
            row_cells[0].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.RIGHT
            for run in row_cells[0].paragraphs[0].runs: run.font.bold = True
            row_cells[5].text = f"R$ {total_geral:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
            for run in row_cells[5].paragraphs[0].runs: run.font.bold = True
            
            p._element.addnext(table._element)
            if placeholder in p.text:
                p.text = p.text.replace(placeholder, "")
            p._table_inserted = True
            return True
    return False

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--template", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--data", required=True)
    args = parser.parse_args()

    try:
        data = json.loads(args.data)
        doc = Document(args.template)
        
        # 1. Signatures first (special handling)
        if "placeholders" in data:
            process_signatures(doc, data["placeholders"])
            
        # 2. Tables
        if "items" in data:
            it = data["items"]
            is_sd = "SD" in args.output or "DFD" in args.output
            dfd_headers = ['ITEM', 'DESCRIÇÃO', 'UNIDADE', 'QTDE.', 'VALOR UNITÁRIO', 'VALOR TOTAL']
            etp_headers = ['Item', 'Descrição', 'Qtd', 'Unid', 'V. Unit', 'V. Total']
            found = False
            for ph in ["{{tabela_itens}}", "___LISTA_ITENS___", "{{estimativa_custo_tabela}}"]:
                if create_items_table(doc, ph, it, dfd_headers if is_sd else etp_headers, "TOTAL:" if is_sd else "TOTAL GERAL:"):
                    found = True; break
            if not found:
                create_items_table(doc, "Estimativa da Demanda", it, etp_headers, "TOTAL GERAL:")
        
        # 3. Instructions (Heading based)
        if "instructions" in data:
            process_instruction_paragraphs(doc, data["instructions"])
            
        # 4. Simple placeholders
        if "placeholders" in data:
            replace_placeholders(doc, data["placeholders"])
            
        # 5. Final cleanup
        remove_red_text(doc)
        
        doc.save(args.output)
        print(f"Document saved to {args.output}")
    except Exception as e:
        import traceback
        traceback.print_exc()
        print(f"Error: {str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
