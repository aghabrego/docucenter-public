#!/usr/bin/env python3
"""
Análisis Rápido de PDF - Versión Simplificada
Análisis sin dependencias complejas de NLTK
"""

import os
import re
import json
import pdfplumber
import PyPDF2
from collections import Counter

class SimplePDFAnalyzer:
    def __init__(self, pdf_path):
        self.pdf_path = pdf_path
        self.text_content = ""
        self.results = {}

    def extract_text(self):
        """Extrae texto usando pdfplumber"""
        print("Extrayendo texto del PDF...")
        try:
            with pdfplumber.open(self.pdf_path) as pdf:
                text_pages = []
                for i, page in enumerate(pdf.pages):
                    page_text = page.extract_text()
                    if page_text:
                        text_pages.append(f"=== PÁGINA {i+1} ===\n{page_text}\n")

                self.text_content = "\n".join(text_pages)
                print(f"✓ Extraído texto de {len(pdf.pages)} páginas")
                return True
        except Exception as e:
            print(f"Error extrayendo texto: {e}")
            return False

    def basic_stats(self):
        """Estadísticas básicas del documento"""
        if not self.text_content:
            return

        lines = [line.strip() for line in self.text_content.split('\n') if line.strip()]
        words = re.findall(r'\b\w+\b', self.text_content.lower())
        sentences = re.split(r'[.!?]+', self.text_content)
        sentences = [s.strip() for s in sentences if len(s.strip()) > 10]

        self.results['basic_stats'] = {
            'total_characters': len(self.text_content),
            'total_lines': len(lines),
            'total_words': len(words),
            'total_sentences': len(sentences),
            'avg_words_per_sentence': len(words) / len(sentences) if sentences else 0
        }

    def find_key_terms(self):
        """Busca términos clave relacionados con facturación electrónica"""
        if not self.text_content:
            return

        # Términos específicos de facturación electrónica y normativas
        key_patterns = {
            'facturacion_electronica': [
                r'factura\s+electr[óo]nica', r'fe\b', r'facturaci[óo]n\s+electr[óo]nica',
                r'documento\s+electr[óo]nico', r'comprobante\s+fiscal'
            ],
            'documentos_fiscales': [
                r'nota\s+de\s+cr[ée]dito', r'nota\s+de\s+d[ée]bito', r'factura\s+de\s+venta',
                r'documento\s+equivalente', r'comprobante\s+de\s+pago'
            ],
            'tecnologias': [
                r'\bxml\b', r'\bxsd\b', r'\bsoap\b', r'\bwsdl\b', r'\brest\b', r'\bapi\b',
                r'\bubl\b', r'\bedi\b', r'web\s+service', r'servicio\s+web'
            ],
            'seguridad': [
                r'firma\s+digital', r'certificado\s+digital', r'\bsha\b', r'\brsa\b',
                r'autenticaci[óo]n', r'validaci[óo]n', r'encriptaci[óo]n'
            ],
            'entidades': [
                r'\bdian\b', r'\bsunat\b', r'\bsat\b', r'\balanube\b', r'\bmeypar\b',
                r'proveedor\s+tecnol[óo]gico', r'pac\b', r'pse\b'
            ],
            'identificacion': [
                r'\bruc\b', r'\bnit\b', r'\bcuit\b', r'\bdv\b', r'identificaci[óo]n\s+tributaria',
                r'n[úu]mero\s+de\s+documento'
            ],
            'formatos': [
                r'\.xml\b', r'\.pdf\b', r'\.xsd\b', r'\.p12\b', r'\.cer\b', r'\.pem\b'
            ],
            'protocolos': [
                r'\bhttps\b', r'\btls\b', r'\bssl\b', r'protocolo\s+seguro'
            ],
            'normativas': [
                r'resoluci[óo]n\b', r'decreto\b', r'ley\b', r'normativa\b', r'reglamento\b',
                r'est[áa]ndar\b', r'especificaci[óo]n\s+t[ée]cnica'
            ]
        }

        found_terms = {}
        text_lower = self.text_content.lower()

        for category, patterns in key_patterns.items():
            all_matches = []
            for pattern in patterns:
                matches = re.findall(pattern, text_lower, re.IGNORECASE)
                all_matches.extend(matches)

            if all_matches:
                unique_matches = list(set(all_matches))
                found_terms[category] = {
                    'count': len(all_matches),
                    'unique_count': len(unique_matches),
                    'terms': unique_matches[:10]  # Primeros 10 términos únicos
                }

        self.results['key_terms'] = found_terms

    def extract_references(self):
        """Extrae referencias, URLs, fechas, etc."""
        if not self.text_content:
            return

        patterns = {
            'urls': r'https?://[^\s<>"{}|\\^`\[\]]+',
            'emails': r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b',
            'dates': r'\b\d{1,2}[/-]\d{1,2}[/-]\d{4}\b|\b\d{4}[/-]\d{2}[/-]\d{2}\b',
            'versions': r'\bv?\d+\.\d+(?:\.\d+)?\b',
            'phone_numbers': r'\b\d{3}[-.]?\d{3}[-.]?\d{4}\b|\b\(\d{3}\)\s*\d{3}[-.]?\d{4}\b',
            'rfc_references': r'\brfc\s*\d+\b',
            'iso_standards': r'\biso\s*\d+(?:-\d+)*\b',
            'codigo_pais': r'\b[A-Z]{2}\b(?=\s|$)',  # Códigos de país de 2 letras
            'numeros_documento': r'\b\d{8,15}\b'  # Números largos (posibles documentos)
        }

        references = {}
        for ref_type, pattern in patterns.items():
            matches = re.findall(pattern, self.text_content, re.IGNORECASE)
            if matches:
                # Filtrar y limpiar resultados
                unique_matches = list(set(matches))
                if ref_type == 'codigo_pais':
                    # Filtrar códigos de país comunes
                    paises_comunes = ['CO', 'PA', 'MX', 'PE', 'AR', 'CL', 'EC', 'US', 'ES']
                    unique_matches = [m for m in unique_matches if m in paises_comunes]

                if unique_matches:
                    references[ref_type] = {
                        'count': len(matches),
                        'unique_count': len(unique_matches),
                        'examples': unique_matches[:5]
                    }

        self.results['references'] = references

    def find_sections(self):
        """Identifica secciones principales del documento"""
        if not self.text_content:
            return

        lines = self.text_content.split('\n')
        sections = []

        # Patrones para identificar títulos de sección
        section_patterns = [
            r'^\s*(\d+\.?\s+[A-ZÁÉÍÓÚÑ][^.\n]{5,60})\s*$',  # Numerados
            r'^\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{10,50})\s*$',   # Todo mayúsculas
            r'^\s*(CAP[ÍI]TULO\s+\d+[^.\n]*)\s*$',          # Capítulos
            r'^\s*(ANEXO\s+[A-Z0-9]+[^.\n]*)\s*$',          # Anexos
            r'^\s*(ART[ÍI]CULO\s+\d+[^.\n]*)\s*$',          # Artículos
            r'^\s*(\d+\.\s*[A-ZÁÉÍÓÚÑ][^.\n]{5,60})\s*$'    # Numeración con punto
        ]

        for i, line in enumerate(lines):
            line_clean = line.strip()
            if 5 <= len(line_clean) <= 80:  # Longitud razonable para un título
                for pattern in section_patterns:
                    match = re.match(pattern, line, re.IGNORECASE)
                    if match:
                        sections.append({
                            'line_number': i + 1,
                            'title': match.group(1).strip(),
                            'context': lines[max(0, i-1):min(len(lines), i+3)]
                        })
                        break

        self.results['sections'] = sections

    def word_frequency(self, top_n=30):
        """Análisis de frecuencia de palabras"""
        if not self.text_content:
            return

        # Palabras a excluir (stopwords básicas en español)
        stopwords = {
            'el', 'la', 'de', 'que', 'y', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le',
            'da', 'su', 'por', 'son', 'con', 'para', 'al', 'del', 'los', 'las', 'una',
            'como', 'o', 'pero', 'sus', 'le', 'ya', 'todo', 'esta', 'fue', 'ser',
            'ha', 'si', 'más', 'este', 'puede', 'debe', 'será', 'han', 'están',
            'ante', 'bajo', 'cabe', 'contra', 'desde', 'durante', 'entre', 'hacia',
            'hasta', 'mediante', 'según', 'sin', 'sobre', 'tras'
        }

        # Extraer palabras (solo letras, mínimo 3 caracteres)
        words = re.findall(r'\b[a-záéíóúñ]{3,}\b', self.text_content.lower())

        # Filtrar stopwords
        filtered_words = [word for word in words if word not in stopwords]

        # Contar frecuencias
        word_freq = Counter(filtered_words)

        self.results['word_frequency'] = dict(word_freq.most_common(top_n))

    def extract_tables(self):
        """Extrae información sobre tablas del PDF"""
        try:
            with pdfplumber.open(self.pdf_path) as pdf:
                tables_info = []
                for i, page in enumerate(pdf.pages):
                    tables = page.extract_tables()
                    if tables:
                        for j, table in enumerate(tables):
                            if table and len(table) > 0:
                                tables_info.append({
                                    'page': i + 1,
                                    'table_index': j + 1,
                                    'rows': len(table),
                                    'cols': len(table[0]) if table[0] else 0,
                                    'sample_data': table[:2] if len(table) >= 2 else table
                                })

                self.results['tables'] = {
                    'count': len(tables_info),
                    'tables': tables_info
                }
        except Exception as e:
            print(f"Error extrayendo tablas: {e}")
            self.results['tables'] = {'count': 0, 'tables': []}

    def generate_summary(self):
        """Genera un resumen del análisis"""
        stats = self.results.get('basic_stats', {})
        key_terms = self.results.get('key_terms', {})
        references = self.results.get('references', {})
        sections = self.results.get('sections', [])
        tables = self.results.get('tables', {})

        # Categorizar el documento
        document_type = "Documento Técnico"
        if 'facturacion_electronica' in key_terms:
            document_type = "Documento de Facturación Electrónica"
        elif 'normativas' in key_terms:
            document_type = "Documento Normativo/Legal"

        # Identificar país/región
        region = "No identificada"
        if 'CO' in str(references.get('codigo_pais', {})):
            region = "Colombia"
        elif 'PA' in str(references.get('codigo_pais', {})):
            region = "Panamá"

        # Temas principales
        top_terms = list(self.results.get('word_frequency', {}).keys())[:10]

        summary = {
            'document_type': document_type,
            'region': region,
            'size_indicators': {
                'words': stats.get('total_words', 0),
                'sentences': stats.get('total_sentences', 0),
                'sections': len(sections),
                'tables': tables.get('count', 0)
            },
            'technical_categories': len(key_terms),
            'main_topics': top_terms,
            'has_urls': 'urls' in references,
            'has_technical_specs': 'tecnologias' in key_terms,
            'regulatory_content': 'normativas' in key_terms
        }

        self.results['summary'] = summary

    def run_analysis(self):
        """Ejecuta análisis completo simplificado"""
        print("=== ANÁLISIS RÁPIDO DE PDF ===")
        print(f"Archivo: {os.path.basename(self.pdf_path)}")

        if not self.extract_text():
            return None

        print("Calculando estadísticas básicas...")
        self.basic_stats()

        print("Buscando términos clave...")
        self.find_key_terms()

        print("Extrayendo referencias...")
        self.extract_references()

        print("Identificando secciones...")
        self.find_sections()

        print("Analizando frecuencia de palabras...")
        self.word_frequency()

        print("Extrayendo tablas...")
        self.extract_tables()

        print("Generando resumen...")
        self.generate_summary()

        return self.results

    def print_report(self):
        """Imprime reporte legible del análisis"""
        if not self.results:
            print("No hay resultados para mostrar")
            return

        print("\n" + "="*70)
        print("REPORTE DE ANÁLISIS DEL PDF")
        print("="*70)

        # Resumen ejecutivo
        summary = self.results.get('summary', {})
        print(f"📄 Tipo de documento: {summary.get('document_type', 'N/A')}")
        print(f"🌍 Región: {summary.get('region', 'N/A')}")

        # Estadísticas
        stats = self.results.get('basic_stats', {})
        print(f"\n📊 ESTADÍSTICAS:")
        print(f"   • Palabras: {stats.get('total_words', 0):,}")
        print(f"   • Oraciones: {stats.get('total_sentences', 0):,}")
        print(f"   • Líneas: {stats.get('total_lines', 0):,}")
        if stats.get('avg_words_per_sentence', 0) > 0:
            print(f"   • Promedio palabras/oración: {stats.get('avg_words_per_sentence', 0):.1f}")

        # Términos clave por categoría
        key_terms = self.results.get('key_terms', {})
        if key_terms:
            print(f"\n🔑 TÉRMINOS CLAVE ENCONTRADOS:")
            for category, data in key_terms.items():
                category_name = category.replace('_', ' ').title()
                print(f"   • {category_name}: {data['count']} menciones ({data['unique_count']} únicos)")
                if data['terms'][:3]:
                    examples = ', '.join(data['terms'][:3])
                    print(f"     Ejemplos: {examples}")

        # Secciones principales
        sections = self.results.get('sections', [])
        if sections:
            print(f"\n📑 SECCIONES PRINCIPALES ({len(sections)}):")
            for section in sections[:8]:  # Primeras 8
                print(f"   • {section['title']}")

        # Tablas
        tables = self.results.get('tables', {})
        if tables.get('count', 0) > 0:
            print(f"\n📊 TABLAS ENCONTRADAS: {tables['count']}")
            for table in tables.get('tables', [])[:3]:  # Primeras 3
                print(f"   • Página {table['page']}: {table['rows']}x{table['cols']}")

        # Referencias importantes
        references = self.results.get('references', {})
        important_refs = ['urls', 'emails', 'versions', 'iso_standards']
        found_refs = {k: v for k, v in references.items() if k in important_refs and v}
        if found_refs:
            print(f"\n🔗 REFERENCIAS IMPORTANTES:")
            for ref_type, data in found_refs.items():
                ref_name = ref_type.replace('_', ' ').title()
                print(f"   • {ref_name}: {data['count']} encontradas")
                if ref_type == 'urls' and data['examples']:
                    print(f"     Ejemplo: {data['examples'][0]}")

        # Top palabras
        word_freq = self.results.get('word_frequency', {})
        if word_freq:
            print(f"\n🔤 PALABRAS MÁS FRECUENTES:")
            for word, count in list(word_freq.items())[:15]:
                print(f"   • {word}: {count}")

        print(f"\n✅ Análisis completado exitosamente")

    def save_results(self, json_file, text_file=None):
        """Guarda resultados en archivos"""
        try:
            with open(json_file, 'w', encoding='utf-8') as f:
                json.dump(self.results, f, indent=2, ensure_ascii=False)
            print(f"💾 Resultados guardados en: {json_file}")

            if text_file and self.text_content:
                with open(text_file, 'w', encoding='utf-8') as f:
                    f.write(self.text_content)
                print(f"📝 Texto extraído guardado en: {text_file}")

        except Exception as e:
            print(f"Error guardando archivos: {e}")

def main():
    pdf_path = "/home/weirdolabs/code/docucenter/public/CO_FacturaElectronicaCO_EstudioTecnico_rve02_PANAMA (1).pdf"

    if not os.path.exists(pdf_path):
        print(f"❌ Error: El archivo {pdf_path} no existe")
        return

    # Crear analizador y ejecutar
    analyzer = SimplePDFAnalyzer(pdf_path)
    results = analyzer.run_analysis()

    if results:
        # Mostrar reporte
        analyzer.print_report()

        # Guardar archivos
        analyzer.save_results(
            "docs/technical/pdf_analysis_simple.json",
            "docs/technical/pdf_text_extracted.txt"
        )
    else:
        print("❌ No se pudo completar el análisis")

if __name__ == "__main__":
    main()
