import sys
import json
import argparse
import os
from docx import Document
from docx.shared import Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH

def log(msg):
    print(msg, file=sys.stderr)

def set_run_text(p, text):
    """Sets the text of a paragraph, handling newlines and preserving formatting."""
    if not p.runs:
        p.add_run("")
    
    # Clear existing runs
    for run in p.runs:
        run.text = ""
    
    first_run = p.runs[0]
    lines = str(text if text is not None else "").split("\n")
    first_run.text = lines[0]
    for line in lines[1:]:
        first_run.add_break()
        first_run.add_text(line)

def handle_checkboxes(p, value):
    """Handles ( ) Sim ( ) Não or ( ) Viável checkboxes in a paragraph."""
    if value is None: return
    
    # Convert bool to Sim/Não
    if isinstance(value, bool):
        value = "Sim" if value else "Não"
    
    text = p.text
    patterns = ["( ) Sim", "( ) Não", "( ) Viável", "( ) Viável com restrições", "( ) Inviável", "( ) Alta", "( ) Média", "( ) Baixa"]
    
    modified = False
    for pattern in patterns:
        option_text = pattern.replace("( ) ", "").strip()
        checked_pattern = pattern.replace("( )", "(X)")
        
        # Check if the option is in the text (either checked or unchecked)
        if pattern in text or checked_pattern in text:
            target_pattern = checked_pattern if str(value).lower() == option_text.lower() else pattern
            current_pattern = pattern if str(value).lower() == option_text.lower() else checked_pattern
            
            # Update runs
            for run in p.runs:
                if current_pattern in run.text:
                    run.text = run.text.replace(current_pattern, target_pattern)
                    modified = True
                elif pattern in run.text and str(value).lower() == option_text.lower():
                    run.text = run.text.replace(pattern, checked_pattern)
                    modified = True
    
    # Generic ( ) fallback
    if not modified and "( )" in text and value:
        for run in p.runs:
            if "( )" in run.text:
                run.text = run.text.replace("( )", "(X)", 1)
                modified = True
    
    return modified

def fill_items_table(doc, items, is_sd=False):
    if not items: return False
    
    headers_sd = ['ITEM', 'DESCRIÇÃO', 'UNIDADE', 'QTDE.', 'VALOR UNITÁRIO', 'VALOR TOTAL']
    headers_etp = ['Item', 'Descrição', 'Qtd', 'Unid', 'V. Unit', 'V. Total']
    target_headers = headers_sd if is_sd else headers_etp
    
    for table in doc.tables:
        if len(table.rows) > 0:
            first_row_text = "".join(cell.text.upper() for cell in table.rows[0].cells)
            if sum(1 for h in target_headers if h.upper() in first_row_text) >= 2:
                while len(table.rows) > 1:
                    table._tbl.remove(table.rows[1]._tr)
                
                total_geral = 0
                for i, item in enumerate(items):
                    qty = float(item.get('quantity', 0))
                    val = float(item.get('unit_value', 0))
                    total = qty * val
                    total_geral += total
                    
                    row_cells = table.add_row().cells
                    row_cells[0].text = str(i + 1)
                    row_cells[1].text = str(item.get('description', ''))
                    
                    if is_sd:
                        row_cells[2].text = str(item.get('unit', ''))
                        row_cells[3].text = f"{qty:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                    else:
                        row_cells[2].text = f"{qty:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                        row_cells[3].text = str(item.get('unit', ''))
                        
                    row_cells[4].text = f"R$ {val:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                    row_cells[5].text = f"R$ {total:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                
                row_cells = table.add_row().cells
                row_cells[0].text = "TOTAL:" if is_sd else "TOTAL GERAL:"
                row_cells[0].merge(row_cells[4])
                row_cells[0].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.RIGHT
                row_cells[5].text = f"R$ {total_geral:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                return True

    placeholders = ["{{tabela_itens}}", "___LISTA_ITENS___", "{{estimativa_custo_tabela}}", "Estimativa de Demanda"]
    for i, p in enumerate(doc.paragraphs):
        txt = p.text.strip()
        if any(ph.lower() in txt.lower() for ph in placeholders):
            table = doc.add_table(rows=1, cols=6)
            table.style = 'Table Grid'
            hdr_cells = table.rows[0].cells
            for idx, h in enumerate(target_headers):
                hdr_cells[idx].text = h
                for run in hdr_cells[idx].paragraphs[0].runs: run.font.bold = True
            
            total_geral = 0
            for idx, item in enumerate(items):
                qty = float(item.get('quantity', 0))
                val = float(item.get('unit_value', 0))
                total = qty * val
                total_geral += total
                row_cells = table.add_row().cells
                row_cells[0].text = str(idx + 1)
                row_cells[1].text = str(item.get('description', ''))
                if is_sd:
                    row_cells[2].text = str(item.get('unit', ''))
                    row_cells[3].text = f"{qty:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                else:
                    row_cells[2].text = f"{qty:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                    row_cells[3].text = str(item.get('unit', ''))
                row_cells[4].text = f"R$ {val:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
                row_cells[5].text = f"R$ {total:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
            
            row_cells = table.add_row().cells
            row_cells[0].text = "TOTAL:" if is_sd else "TOTAL GERAL:"
            row_cells[0].merge(row_cells[4])
            row_cells[5].text = f"R$ {total_geral:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
            
            p._element.addnext(table._element)
            if "{{" in p.text or "___" in p.text:
                p.text = ""
            return True
            
    return False

def process_signatures(doc, data):
    for table in doc.tables:
        all_text = "".join(c.text for r in table.rows for c in r.cells).upper()
        if "NOME:" not in all_text or "CPF:" not in all_text:
            continue

        current_suffix = "autor"
        for row in table.rows:
            row_label_text = "".join(c.text for c in row.cells).upper()
            if "SECRETARIA" in row_label_text or "SECRETARIO" in row_label_text or "RESPONSÁVEL" in row_label_text:
                if "AUTOR" not in row_label_text:
                    current_suffix = "secretario"
            elif "AUTOR" in row_label_text:
                current_suffix = "autor"
            
            for cell in row.cells:
                for p in cell.paragraphs:
                    txt = p.text.strip().upper()
                    for label in ["NOME:", "CPF:", "CARGO/FUNÇÃO:", "CARGO:"]:
                        if txt.startswith(label):
                            clean_label = label.split(":")[0]
                            key = f"{clean_label}:_{current_suffix}"
                            val = data.get(key)
                            if val:
                                set_run_text(p, f"{label} {val}")

def process_instructions_in_list(paragraphs, instruction_map):
    used_needles = set()
    for i, p in enumerate(paragraphs):
        text = p.text.strip().rstrip(":")
        if not text: continue
        
        for needle, replacement in instruction_map.items():
            if needle in used_needles and needle not in ["Viabilidade", "Justificativa", "Aplica:"]:
                continue
                
            clean_needle = needle.strip().rstrip(":")
            # Match heading
            if clean_needle.lower() == text.lower() or (len(clean_needle) > 5 and clean_needle.lower() in text.lower()):
                # 1. Check current paragraph for checkboxes
                if "(" in p.text and ")" in p.text:
                    if handle_checkboxes(p, replacement):
                        log(f"Filled checkbox in current paragraph: {text}")
                        used_needles.add(needle)
                        break
                
                # 2. Look ahead
                for offset in range(1, 4):
                    if i + offset < len(paragraphs):
                        target_p = paragraphs[i+offset]
                        t_text = target_p.text.strip()
                        
                        if "(" in t_text and ")" in t_text:
                            handle_checkboxes(target_p, replacement)
                            log(f"Filled checkbox in next paragraph: {t_text}")
                            used_needles.add(needle)
                            break
                        
                        if not t_text or "___" in t_text or "{{" in t_text or "exemplo" in t_text.lower():
                            set_run_text(target_p, replacement)
                            log(f"Filled paragraph: {replacement}")
                            used_needles.add(needle)
                            break
                        
                        if offset == 1 and len(t_text) > 0 and not any(k.lower() in t_text.lower() for k in instruction_map.keys()):
                            set_run_text(target_p, replacement)
                            log(f"Replaced text paragraph: {replacement}")
                            used_needles.add(needle)
                            break
                break

def remove_red_text(doc):
    for p in doc.paragraphs:
        for run in p.runs:
            if run.font.color and run.font.color.rgb:
                color_hex = str(run.font.color.rgb).upper()
                if color_hex in ['FF0000', 'EE0000', 'FF3333', 'ED1C24']:
                    run.text = ""
    for table in doc.tables:
        for row in table.rows:
            for cell in row.cells:
                for p in cell.paragraphs:
                    for run in p.runs:
                        if run.font.color and run.font.color.rgb:
                            color_hex = str(run.font.color.rgb).upper()
                            if color_hex in ['FF0000', 'EE0000', 'FF3333', 'ED1C24']:
                                run.text = ""

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--template", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--data", required=True)
    args = parser.parse_args()

    try:
        data = json.loads(args.data)
        doc = Document(args.template)
        
        filename = os.path.basename(args.output).upper()
        template_name = os.path.basename(args.template).upper()
        is_sd = "MODELO_SD" in template_name or filename.startswith("DFD") or (filename.startswith("SD") and "ETP" not in filename)
        
        log(f"Processing {'SD' if is_sd else 'ETP'} document...")

        if "items" in data:
            fill_items_table(doc, data["items"], is_sd)
            
        if "instructions" in data:
            process_instructions_in_list(doc.paragraphs, data["instructions"])
            for table in doc.tables:
                table_paragraphs = []
                for row in table.rows:
                    for cell in row.cells:
                        table_paragraphs.extend(cell.paragraphs)
                process_instructions_in_list(table_paragraphs, data["instructions"])
            
        if "placeholders" in data:
            process_signatures(doc, data["placeholders"])
            
        placeholders = data.get("placeholders", {})
        for p in doc.paragraphs:
            for k, v in placeholders.items():
                if k in p.text:
                    p.text = p.text.replace(k, str(v if v is not None else ""))
                    
        for table in doc.tables:
            for row in table.rows:
                for cell in row.cells:
                    for p in cell.paragraphs:
                        for k, v in placeholders.items():
                            if k in p.text:
                                p.text = p.text.replace(k, str(v if v is not None else ""))

        remove_red_text(doc)
        doc.save(args.output)
        log(f"Success: {args.output}")
    except Exception as e:
        import traceback
        log(traceback.format_exc())
        sys.exit(1)

if __name__ == "__main__":
    main()
