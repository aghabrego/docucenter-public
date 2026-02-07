#!/usr/bin/env python3
"""
Análisis Avanzado de PDF - Factura Electrónica Colombia/Panamá
Extrae y analiza texto del documento técnico de facturación electrónica
"""

import os
import re
import pandas as pd
import pdfplumber
import PyPDF2
from collections import Counter
from textblob import TextBlob
import nltk
from nltk.corpus import stopwords
from nltk.tokenize import word_tokenize, sent_tokenize
import json

# Descargar recursos de NLTK si es necesario
try:
    nltk.data.find('tokenizers/punkt_tab')
except LookupError:
    print("Descargando recursos de NLTK...")
    nltk.download('punkt_tab')
    nltk.download('punkt')
    nltk.download('stopwords')

class PDFAnalyzer:
    def __init__(self, pdf_path):
        self.pdf_path = pdf_path
        self.text_content = ""
        self.metadata = {}
        self.analysis_results = {}

    def extract_text_pdfplumber(self):
        """Extrae texto usando pdfplumber (mejor para tablas y formato)"""
        print("Extrayendo texto con pdfplumber...")
        try:
            with pdfplumber.open(self.pdf_path) as pdf:
                text_pages = []
                for i, page in enumerate(pdf.pages):
                    page_text = page.extract_text()
                    if page_text:
                        text_pages.append(f"=== PÁGINA {i+1} ===\n{page_text}\n")

                self.text_content = "\n".join(text_pages)
                print(f"Extraído texto de {len(pdf.pages)} páginas")
                return True
        except Exception as e:
            print(f"Error con pdfplumber: {e}")
            return False

    def extract_text_pypdf2(self):
        """Extrae texto usando PyPDF2 (backup)"""
        print("Extrayendo texto con PyPDF2...")
        try:
            with open(self.pdf_path, 'rb') as file:
                pdf_reader = PyPDF2.PdfReader(file)
                text_pages = []

                for i, page in enumerate(pdf_reader.pages):
                    page_text = page.extract_text()
                    if page_text:
                        text_pages.append(f"=== PÁGINA {i+1} ===\n{page_text}\n")

                self.text_content = "\n".join(text_pages)
                print(f"Extraído texto de {len(pdf_reader.pages)} páginas")
                return True
        except Exception as e:
            print(f"Error con PyPDF2: {e}")
            return False

    def extract_metadata(self):
        """Extrae metadatos del PDF"""
        try:
            with open(self.pdf_path, 'rb') as file:
                pdf_reader = PyPDF2.PdfReader(file)
                if pdf_reader.metadata:
                    self.metadata = {
                        'title': pdf_reader.metadata.get('/Title', 'N/A'),
                        'author': pdf_reader.metadata.get('/Author', 'N/A'),
                        'subject': pdf_reader.metadata.get('/Subject', 'N/A'),
                        'creator': pdf_reader.metadata.get('/Creator', 'N/A'),
                        'producer': pdf_reader.metadata.get('/Producer', 'N/A'),
                        'creation_date': pdf_reader.metadata.get('/CreationDate', 'N/A'),
                        'modification_date': pdf_reader.metadata.get('/ModDate', 'N/A'),
                        'pages': len(pdf_reader.pages)
                    }
        except Exception as e:
            print(f"Error extrayendo metadatos: {e}")
            self.metadata = {'pages': 'N/A'}

    def analyze_text_structure(self):
        """Analiza la estructura del texto"""
        if not self.text_content:
            return

        lines = self.text_content.split('\n')
        sentences = sent_tokenize(self.text_content, language='spanish')
        words = word_tokenize(self.text_content.lower(), language='spanish')

        # Filtrar stopwords en español
        try:
            stop_words = set(stopwords.words('spanish'))
        except:
            stop_words = set()

        filtered_words = [word for word in words if word.isalnum() and word not in stop_words]

        self.analysis_results['structure'] = {
            'total_characters': len(self.text_content),
            'total_lines': len(lines),
            'total_sentences': len(sentences),
            'total_words': len(words),
            'filtered_words': len(filtered_words),
            'avg_words_per_sentence': len(words) / len(sentences) if sentences else 0,
            'avg_chars_per_word': len(self.text_content) / len(words) if words else 0
        }

    def find_technical_terms(self):
        """Encuentra términos técnicos relacionados con facturación electrónica"""
        if not self.text_content:
            return

        # Términos técnicos de facturación electrónica
        technical_patterns = {
            'xml_schemas': r'\b[A-Z]+Schema\b|\bXML\b|\bXSD\b',
            'fiscal_codes': r'\bRUC\b|\bDV\b|\bNIT\b|\bCUIT\b',
            'document_types': r'factura\s+electr[óo]nica|nota\s+de\s+cr[ée]dito|nota\s+de\s+d[ée]bito',
            'encryption': r'\bSHA\b|\bRSA\b|\bMD5\b|\bfirma\s+digital\b',
            'web_services': r'\bSOAP\b|\bWSDL\b|\bREST\b|\bAPI\b',
            'standards': r'\bUBL\b|\bEDI\b|\bASN\.1\b',
            'formats': r'\.xml\b|\.pdf\b|\.xsd\b|\.wsdl\b',
            'protocols': r'\bHTTPS\b|\bTLS\b|\bSSL\b',
            'validation': r'validaci[óo]n|verificaci[óo]n|autenticaci[óo]n',
            'compliance': r'normativ[ao]|reglament[ao]|cumplimiento'
        }

        found_terms = {}
        for category, pattern in technical_patterns.items():
            matches = re.findall(pattern, self.text_content, re.IGNORECASE)
            if matches:
                found_terms[category] = {
                    'count': len(matches),
                    'unique_terms': list(set([m.lower() for m in matches]))
                }

        self.analysis_results['technical_terms'] = found_terms

    def extract_urls_and_references(self):
        """Extrae URLs, correos y referencias"""
        if not self.text_content:
            return

        # Patrones para diferentes tipos de referencias
        patterns = {
            'urls': r'https?://[^\s]+',
            'emails': r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b',
            'phone_numbers': r'\b\d{3}[-.]?\d{3}[-.]?\d{4}\b|\b\(\d{3}\)\s*\d{3}[-.]?\d{4}\b',
            'dates': r'\b\d{1,2}/\d{1,2}/\d{4}\b|\b\d{4}-\d{2}-\d{2}\b',
            'versions': r'\bv?\d+\.\d+(?:\.\d+)?\b',
            'rfc_references': r'\bRFC\s*\d+\b',
            'iso_standards': r'\bISO\s*\d+(?:-\d+)*\b'
        }

        references = {}
        for ref_type, pattern in patterns.items():
            matches = re.findall(pattern, self.text_content, re.IGNORECASE)
            if matches:
                references[ref_type] = list(set(matches))

        self.analysis_results['references'] = references

    def identify_document_sections(self):
        """Identifica secciones del documento basado en patrones"""
        if not self.text_content:
            return

        # Patrones comunes de secciones en documentos técnicos
        section_patterns = [
            r'^(\d+\.?\s*[A-ZÁÉÍÓÚÑ][^.\n]*)',
            r'^([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{10,50})\s*$',
            r'^(CAPÍTULO\s+\d+[^.\n]*)',
            r'^(ANEXO\s+[A-Z0-9]+[^.\n]*)',
            r'^(ARTÍCULO\s+\d+[^.\n]*)'
        ]

        sections = []
        lines = self.text_content.split('\n')

        for i, line in enumerate(lines):
            line = line.strip()
            if len(line) < 5:
                continue

            for pattern in section_patterns:
                match = re.match(pattern, line, re.IGNORECASE)
                if match:
                    sections.append({
                        'line_number': i + 1,
                        'title': match.group(1),
                        'type': 'section_header'
                    })
                    break

        self.analysis_results['sections'] = sections

    def generate_word_frequency(self, top_n=20):
        """Genera análisis de frecuencia de palabras"""
        if not self.text_content:
            return

        words = word_tokenize(self.text_content.lower(), language='spanish')

        # Filtrar palabras cortas y stopwords
        try:
            stop_words = set(stopwords.words('spanish'))
        except:
            stop_words = set()

        # Agregar stopwords técnicas comunes
        technical_stopwords = {'debe', 'puede', 'será', 'como', 'para', 'con', 'por', 'del', 'las', 'los', 'una', 'uno', 'este', 'esta'}
        stop_words.update(technical_stopwords)

        filtered_words = [
            word for word in words
            if len(word) > 3 and word.isalnum() and word not in stop_words
        ]

        word_freq = Counter(filtered_words)
        self.analysis_results['word_frequency'] = dict(word_freq.most_common(top_n))

    def extract_tables_and_lists(self):
        """Extrae tablas y listas del PDF"""
        try:
            with pdfplumber.open(self.pdf_path) as pdf:
                tables = []
                for i, page in enumerate(pdf.pages):
                    page_tables = page.extract_tables()
                    if page_tables:
                        for j, table in enumerate(page_tables):
                            tables.append({
                                'page': i + 1,
                                'table_index': j,
                                'rows': len(table),
                                'cols': len(table[0]) if table else 0,
                                'preview': table[:3] if table else []  # Primeras 3 filas
                            })

                self.analysis_results['tables'] = tables
        except Exception as e:
            print(f"Error extrayendo tablas: {e}")
            self.analysis_results['tables'] = []

    def sentiment_analysis(self):
        """Análisis básico de sentimiento (aunque sea un documento técnico)"""
        if not self.text_content:
            return

        try:
            blob = TextBlob(self.text_content)

            self.analysis_results['sentiment'] = {
                'polarity': blob.sentiment.polarity,
                'subjectivity': blob.sentiment.subjectivity,
                'interpretation': self._interpret_sentiment(blob.sentiment)
            }
        except Exception as e:
            print(f"Error en análisis de sentimiento: {e}")

    def _interpret_sentiment(self, sentiment):
        """Interpreta los valores de sentimiento"""
        polarity_desc = "neutral"
        if sentiment.polarity > 0.1:
            polarity_desc = "positivo"
        elif sentiment.polarity < -0.1:
            polarity_desc = "negativo"

        subjectivity_desc = "objetivo"
        if sentiment.subjectivity > 0.5:
            subjectivity_desc = "subjetivo"

        return f"Tono {polarity_desc}, estilo {subjectivity_desc}"

    def run_full_analysis(self):
        """Ejecuta análisis completo del PDF"""
        print(f"=== ANÁLISIS COMPLETO DE PDF ===")
        print(f"Archivo: {self.pdf_path}")

        # Extraer texto
        success = self.extract_text_pdfplumber()
        if not success:
            success = self.extract_text_pypdf2()

        if not success:
            print("Error: No se pudo extraer texto del PDF")
            return None

        # Extraer metadatos
        self.extract_metadata()

        # Ejecutar análisis
        print("Ejecutando análisis de estructura...")
        self.analyze_text_structure()

        print("Buscando términos técnicos...")
        self.find_technical_terms()

        print("Extrayendo referencias...")
        self.extract_urls_and_references()

        print("Identificando secciones...")
        self.identify_document_sections()

        print("Generando frecuencia de palabras...")
        self.generate_word_frequency()

        print("Extrayendo tablas...")
        self.extract_tables_and_lists()

        print("Análisis de sentimiento...")
        self.sentiment_analysis()

        return self.generate_report()

    def generate_report(self):
        """Genera reporte completo del análisis"""
        report = {
            'metadata': self.metadata,
            'analysis': self.analysis_results,
            'summary': self._generate_summary()
        }

        return report

    def _generate_summary(self):
        """Genera resumen ejecutivo del análisis"""
        structure = self.analysis_results.get('structure', {})
        technical = self.analysis_results.get('technical_terms', {})
        sections = self.analysis_results.get('sections', [])
        tables = self.analysis_results.get('tables', [])

        summary = {
            'document_size': f"{structure.get('total_words', 0)} palabras en {structure.get('total_sentences', 0)} oraciones",
            'technical_complexity': f"{len(technical)} categorías de términos técnicos encontradas",
            'structure_elements': f"{len(sections)} secciones, {len(tables)} tablas",
            'main_topics': list(self.analysis_results.get('word_frequency', {}).keys())[:5],
            'document_type': self._classify_document_type()
        }

        return summary

    def _classify_document_type(self):
        """Clasifica el tipo de documento basado en contenido"""
        technical_terms = self.analysis_results.get('technical_terms', {})

        if 'document_types' in technical_terms:
            return "Documento de Facturación Electrónica"
        elif 'standards' in technical_terms or 'xml_schemas' in technical_terms:
            return "Documento Técnico de Estándares"
        elif 'compliance' in technical_terms:
            return "Documento Normativo"
        else:
            return "Documento Técnico General"

    def save_analysis_results(self, output_file):
        """Guarda los resultados del análisis en archivo JSON"""
        try:
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump({
                    'metadata': self.metadata,
                    'analysis': self.analysis_results
                }, f, indent=2, ensure_ascii=False)
            print(f"Resultados guardados en: {output_file}")
        except Exception as e:
            print(f"Error guardando resultados: {e}")

    def save_extracted_text(self, output_file):
        """Guarda el texto extraído en archivo"""
        try:
            with open(output_file, 'w', encoding='utf-8') as f:
                f.write(self.text_content)
            print(f"Texto extraído guardado en: {output_file}")
        except Exception as e:
            print(f"Error guardando texto: {e}")

def main():
    # Ruta del PDF
    pdf_path = "/home/weirdolabs/code/docucenter/public/CO_FacturaElectronicaCO_EstudioTecnico_rve02_PANAMA (1).pdf"

    if not os.path.exists(pdf_path):
        print(f"Error: El archivo {pdf_path} no existe")
        return

    # Crear analizador y ejecutar análisis completo
    analyzer = PDFAnalyzer(pdf_path)
    results = analyzer.run_full_analysis()

    if results:
        print("\n" + "="*60)
        print("RESUMEN EJECUTIVO")
        print("="*60)

        # Mostrar metadatos
        print(f"📄 Título: {results['metadata'].get('title', 'N/A')}")
        print(f"👤 Autor: {results['metadata'].get('author', 'N/A')}")
        print(f"📊 Páginas: {results['metadata'].get('pages', 'N/A')}")

        # Mostrar estadísticas de estructura
        structure = results['analysis'].get('structure', {})
        print(f"\n📈 ESTADÍSTICAS:")
        print(f"   • Palabras: {structure.get('total_words', 0):,}")
        print(f"   • Oraciones: {structure.get('total_sentences', 0):,}")
        print(f"   • Líneas: {structure.get('total_lines', 0):,}")
        print(f"   • Promedio palabras/oración: {structure.get('avg_words_per_sentence', 0):.1f}")

        # Mostrar términos técnicos
        technical = results['analysis'].get('technical_terms', {})
        if technical:
            print(f"\n🔧 TÉRMINOS TÉCNICOS ENCONTRADOS:")
            for category, data in technical.items():
                print(f"   • {category.upper()}: {data['count']} ocurrencias")
                if data['unique_terms'][:3]:  # Mostrar primeros 3
                    print(f"     Ejemplos: {', '.join(data['unique_terms'][:3])}")

        # Mostrar palabras más frecuentes
        word_freq = results['analysis'].get('word_frequency', {})
        if word_freq:
            print(f"\n🔤 PALABRAS MÁS FRECUENTES:")
            for word, count in list(word_freq.items())[:10]:
                print(f"   • {word}: {count}")

        # Mostrar secciones encontradas
        sections = results['analysis'].get('sections', [])
        if sections:
            print(f"\n📑 SECCIONES IDENTIFICADAS ({len(sections)}):")
            for section in sections[:10]:  # Primeras 10
                print(f"   • Línea {section['line_number']}: {section['title'][:60]}...")

        # Mostrar tablas
        tables = results['analysis'].get('tables', [])
        if tables:
            print(f"\n📊 TABLAS ENCONTRADAS ({len(tables)}):")
            for table in tables[:5]:  # Primeras 5
                print(f"   • Página {table['page']}: {table['rows']}x{table['cols']}")

        # Mostrar referencias
        references = results['analysis'].get('references', {})
        if references:
            print(f"\n🔗 REFERENCIAS ENCONTRADAS:")
            for ref_type, refs in references.items():
                if refs:
                    print(f"   • {ref_type.upper()}: {len(refs)} elementos")
                    if ref_type == 'urls' and refs:
                        print(f"     Ejemplo: {refs[0]}")

        # Guardar archivos de salida
        print(f"\n💾 GUARDANDO RESULTADOS...")
        analyzer.save_analysis_results("docs/technical/pdf_analysis_results.json")
        analyzer.save_extracted_text("docs/technical/pdf_extracted_text.txt")

        print(f"\n✅ ANÁLISIS COMPLETO FINALIZADO")

    else:
        print("❌ No se pudo completar el análisis")

if __name__ == "__main__":
    main()
